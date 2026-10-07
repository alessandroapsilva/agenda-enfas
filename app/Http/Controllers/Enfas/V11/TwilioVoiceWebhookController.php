<?php

namespace App\Http\Controllers\Enfas\V11;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TwilioVoiceWebhookController extends Controller
{
    public function answer(
        Request $request,
        int $attempt
    ) {
        abort_unless(
            $this->validSignature($request),
            401
        );

        $context = $this->context(
            $attempt
        );

        abort_unless(
            $context,
            404
        );

        $this->bindCall(
            $attempt,
            $request
        );

        $start = Carbon::parse(
            $context->start_at
        );

        $date = $start
            ->locale('pt_BR')
            ->translatedFormat('d \d\e F');

        $time = $start->format('H:i');

        $action = route(
            'voice.twilio.gather',
            ['attempt' => $attempt],
            true
        );

        $voice = $this->xml(
            (string) config(
                'services.voice.twilio.voice',
                'Polly.Camila-Neural'
            )
        );

        $prompt = $this->xml(
            'Olá. Aqui é a assistente automática da ENFAS Agenda. '
            .'Existe um agendamento para '.$date
            .' às '.$time.'. '
            .'Para confirmar, diga confirmar ou tecle 1. '
            .'Para cancelar, diga cancelar ou tecle 2. '
            .'Para solicitar reagendamento, diga reagendar ou tecle 3.'
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Response>'
            .'<Gather '
            .'input="dtmf speech" '
            .'numDigits="1" '
            .'timeout="5" '
            .'speechTimeout="auto" '
            .'actionOnEmptyResult="true" '
            .'language="pt-BR" '
            .'speechModel="phone_call" '
            .'hints="confirmar,cancelar,reagendar,remarcar" '
            .'action="'.$this->xml($action).'" '
            .'method="POST">'
            .'<Say language="pt-BR" voice="'.$voice.'">'
            .$prompt
            .'</Say>'
            .'</Gather>'
            .'<Say language="pt-BR" voice="'.$voice.'">'
            .'Não recebemos uma resposta. '
            .'Nossa equipe poderá entrar em contato novamente.'
            .'</Say>'
            .'<Hangup/>'
            .'</Response>';

        return $this->twiml($xml);
    }

    public function gather(
        Request $request,
        int $attempt
    ) {
        abort_unless(
            $this->validSignature($request),
            401
        );

        $context = $this->context(
            $attempt
        );

        abort_unless(
            $context,
            404
        );

        $this->bindCall(
            $attempt,
            $request
        );

        $digits = trim(
            (string) $request->input(
                'Digits',
                ''
            )
        );

        $speech = trim(
            (string) $request->input(
                'SpeechResult',
                ''
            )
        );

        $outcome = $this->interpret(
            $digits,
            $speech
        );

        $message = match ($outcome) {
            'confirmed' =>
                'Pronto. Sua presença foi confirmada. Obrigado.',

            'cancelled' =>
                'Pronto. O cancelamento foi registrado. Obrigado.',

            'callback' =>
                'Entendido. Registramos sua solicitação de reagendamento. '
                .'Nossa equipe entrará em contato.',

            default =>
                'Não conseguimos identificar sua resposta. '
                .'Nossa equipe poderá entrar em contato novamente.',
        };

        $this->completeAttempt(
            $attempt,
            $outcome,
            $speech !== ''
                ? 'Resposta de voz: '
                    .mb_substr(
                        $speech,
                        0,
                        300
                    )
                : (
                    $digits !== ''
                        ? 'Resposta DTMF: '.$digits
                        : 'Sem resposta detectada.'
                )
        );

        if ($outcome === 'confirmed') {
            $this->confirmAppointment(
                $context->appointment_id,
                $attempt
            );
        }

        if ($outcome === 'cancelled') {
            $this->cancelAppointment(
                $context->appointment_id,
                $attempt
            );
        }

        if ($outcome === 'callback') {
            $this->appointmentEvent(
                $context->appointment_id,
                'voice_reschedule_requested',
                'Reagendamento solicitado por ligação automática',
                [
                    'attempt_id' => $attempt,
                ]
            );
        }

        $voice = $this->xml(
            (string) config(
                'services.voice.twilio.voice',
                'Polly.Camila-Neural'
            )
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Response>'
            .'<Say language="pt-BR" voice="'.$voice.'">'
            .$this->xml($message)
            .'</Say>'
            .'<Hangup/>'
            .'</Response>';

        return $this->twiml($xml);
    }

    public function status(
        Request $request,
        int $attempt
    ) {
        abort_unless(
            $this->validSignature($request),
            401
        );

        $row = DB::table(
            'confirmation_attempts'
        )
            ->where(
                'id',
                $attempt
            )
            ->first();

        abort_unless(
            $row,
            404
        );

        $this->bindCall(
            $attempt,
            $request
        );

        $callStatus = strtolower(
            trim(
                (string) $request->input(
                    'CallStatus',
                    ''
                )
            )
        );

        $terminal = [
            'completed',
            'busy',
            'failed',
            'no-answer',
            'canceled',
        ];

        if (! in_array(
            $callStatus,
            $terminal,
            true
        )) {
            return response(
                'OK',
                200
            );
        }

        $fresh = DB::table(
            'confirmation_attempts'
        )
            ->where(
                'id',
                $attempt
            )
            ->first();

        if (! $fresh) {
            return response(
                'OK',
                200
            );
        }

        if ($fresh->status === 'completed'
            && $fresh->outcome) {
            return response(
                'OK',
                200
            );
        }

        $outcome = match ($callStatus) {
            'busy' => 'busy',
            'no-answer' => 'no_answer',
            'failed',
            'canceled' => 'provider_failed',
            default => 'no_input',
        };

        $this->completeAttempt(
            $attempt,
            $outcome,
            'Twilio CallStatus: '
                .$callStatus
        );

        return response(
            'OK',
            200
        );
    }

    private function context(
        int $attempt
    ): ?object {
        if (! Schema::hasTable(
            'confirmation_attempts'
        )) {
            return null;
        }

        return DB::table(
            'confirmation_attempts as ca'
        )
            ->join(
                'appointments as a',
                'a.id',
                '=',
                'ca.appointment_id'
            )
            ->where(
                'ca.id',
                $attempt
            )
            ->where(
                'ca.channel',
                'voice'
            )
            ->first([
                'ca.id',
                'ca.appointment_id',
                'ca.status',
                'ca.outcome',
                'ca.external_id',
                'a.start_at',
                'a.status as appointment_status',
                'a.confirmation_status',
            ]);
    }

    private function bindCall(
        int $attempt,
        Request $request
    ): void {
        $callSid = trim(
            (string) $request->input(
                'CallSid',
                ''
            )
        );

        $current = DB::table(
            'confirmation_attempts'
        )
            ->where(
                'id',
                $attempt
            )
            ->first([
                'id',
                'status',
                'external_id',
            ]);

        abort_unless(
            $current,
            404
        );

        if ($callSid !== ''
            && $current->external_id
            && ! hash_equals(
                (string) $current->external_id,
                $callSid
            )) {
            abort(
                409,
                'CallSid não corresponde à tentativa.'
            );
        }

        $update = [
            'status' =>
                'in_progress',
            'started_at' =>
                now(),
            'updated_at' =>
                now(),
        ];

        if ($callSid !== '') {
            $update['external_id'] =
                $callSid;
        }

        DB::table(
            'confirmation_attempts'
        )
            ->where(
                'id',
                $attempt
            )
            ->whereIn(
                'status',
                ['queued', 'in_progress']
            )
            ->update($update);
    }

    private function completeAttempt(
        int $attempt,
        string $outcome,
        ?string $notes = null
    ): void {
        DB::table(
            'confirmation_attempts'
        )
            ->where(
                'id',
                $attempt
            )
            ->update([
                'status' =>
                    'completed',
                'outcome' =>
                    $outcome,
                'completed_at' =>
                    now(),
                'notes' =>
                    $notes,
                'updated_at' =>
                    now(),
            ]);
    }

    private function confirmAppointment(
        int $appointmentId,
        int $attempt
    ): void {
        DB::transaction(
            function () use (
                $appointmentId,
                $attempt
            ) {
                DB::table(
                    'appointments'
                )
                    ->where(
                        'id',
                        $appointmentId
                    )
                    ->whereNotIn(
                        'status',
                        [
                            'cancelled',
                            'canceled',
                            'completed',
                        ]
                    )
                    ->update([
                        'status' =>
                            'confirmed',
                        'confirmation_status' =>
                            'confirmed',
                        'confirmation_channel' =>
                            'phone_ai',
                        'confirmed_at' =>
                            now(),
                        'cancelled_at' =>
                            null,
                        'cancellation_reason' =>
                            null,
                        'updated_at' =>
                            now(),
                    ]);

                $this->appointmentEvent(
                    $appointmentId,
                    'voice_confirmed',
                    'Presença confirmada por ligação automática',
                    [
                        'attempt_id' =>
                            $attempt,
                    ]
                );
            }
        );
    }

    private function cancelAppointment(
        int $appointmentId,
        int $attempt
    ): void {
        DB::transaction(
            function () use (
                $appointmentId,
                $attempt
            ) {
                DB::table(
                    'appointments'
                )
                    ->where(
                        'id',
                        $appointmentId
                    )
                    ->whereNotIn(
                        'status',
                        [
                            'cancelled',
                            'canceled',
                            'completed',
                        ]
                    )
                    ->update([
                        'status' =>
                            'cancelled',
                        'confirmation_status' =>
                            'cancelled',
                        'confirmation_channel' =>
                            'phone_ai',
                        'confirmed_at' =>
                            null,
                        'cancelled_at' =>
                            now(),
                        'updated_at' =>
                            now(),
                    ]);

                $this->appointmentEvent(
                    $appointmentId,
                    'voice_cancelled',
                    'Agendamento cancelado por ligação automática',
                    [
                        'attempt_id' =>
                            $attempt,
                    ]
                );
            }
        );
    }

    private function appointmentEvent(
        int $appointmentId,
        string $type,
        string $title,
        array $metadata = []
    ): void {
        if (! Schema::hasTable(
            'appointment_events'
        )) {
            return;
        }

        DB::table(
            'appointment_events'
        )->insert([
            'appointment_id' =>
                $appointmentId,
            'user_id' =>
                null,
            'event_type' =>
                $type,
            'title' =>
                $title,
            'description' =>
                null,
            'metadata' =>
                $metadata !== []
                    ? json_encode(
                        $metadata,
                        JSON_UNESCAPED_UNICODE
                    )
                    : null,
            'occurred_at' =>
                now(),
            'created_at' =>
                now(),
            'updated_at' =>
                now(),
        ]);
    }

    private function interpret(
        string $digits,
        string $speech
    ): string {
        if ($digits === '1') {
            return 'confirmed';
        }

        if ($digits === '2') {
            return 'cancelled';
        }

        if ($digits === '3') {
            return 'callback';
        }

        $normalized = Str::of(
            $speech
        )
            ->ascii()
            ->lower()
            ->squish()
            ->toString();

        if ($normalized === '') {
            return 'no_input';
        }

        if (str_contains(
            $normalized,
            'reagend'
        )
            || str_contains(
                $normalized,
                'remarc'
            )
            || str_contains(
                $normalized,
                'mudar horario'
            )) {
            return 'callback';
        }

        if (str_contains(
            $normalized,
            'cancel'
        )
            || str_contains(
                $normalized,
                'desmarc'
            )
            || str_contains(
                $normalized,
                'nao vou'
            )
            || str_contains(
                $normalized,
                'nao poderei'
            )) {
            return 'cancelled';
        }

        if (str_contains(
            $normalized,
            'confirm'
        )
            || $normalized === 'sim'
            || str_contains(
                $normalized,
                'vou comparecer'
            )) {
            return 'confirmed';
        }

        return 'no_input';
    }

    private function validSignature(
        Request $request
    ): bool {
        if (! (bool) config(
            'services.voice.twilio.validate_webhooks',
            true
        )) {
            return true;
        }

        $token = (string) config(
            'services.voice.twilio.auth_token',
            ''
        );

        $provided = (string) $request->header(
            'X-Twilio-Signature',
            ''
        );

        if ($token === ''
            || $provided === '') {
            return false;
        }

        $urls = [
            $request->fullUrl(),
            $this->canonicalUrl(
                $request
            ),
        ];

        foreach (array_unique($urls) as $url) {
            if ($this->signatureFor(
                $url,
                $request->post(),
                $token
            ) === $provided) {
                return true;
            }
        }

        return false;
    }

    private function canonicalUrl(
        Request $request
    ): string {
        $base = rtrim(
            (string) config(
                'app.url'
            ),
            '/'
        );

        $url = $base
            .'/'
            .ltrim(
                $request->path(),
                '/'
            );

        $query =
            $request->getQueryString();

        return $query
            ? $url.'?'.$query
            : $url;
    }

    private function signatureFor(
        string $url,
        array $parameters,
        string $token
    ): string {
        ksort(
            $parameters,
            SORT_STRING
        );

        $data = $url;

        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $data .= $key
                        .(string) $item;
                }

                continue;
            }

            $data .= $key
                .(string) $value;
        }

        return base64_encode(
            hash_hmac(
                'sha1',
                $data,
                $token,
                true
            )
        );
    }

    private function twiml(
        string $xml
    ) {
        return response(
            $xml,
            200
        )->header(
            'Content-Type',
            'text/xml; charset=UTF-8'
        );
    }

    private function xml(
        string $value
    ): string {
        return htmlspecialchars(
            $value,
            ENT_XML1
                | ENT_QUOTES,
            'UTF-8'
        );
    }
}
