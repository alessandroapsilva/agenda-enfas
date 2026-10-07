<?php

namespace App\Services\Enfas\Voice;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TwilioVoiceProvider implements VoiceProvider
{
    public function name(): string
    {
        return 'twilio';
    }

    public function enabled(): bool
    {
        return (bool) config(
            'services.voice.enabled',
            false
        )
            && config(
                'services.voice.provider',
                'twilio'
            ) === 'twilio';
    }

    public function ready(): bool
    {
        return $this->enabled()
            && filled($this->accountSid())
            && filled($this->authToken())
            && filled($this->fromNumber())
            && filled(config('app.url'));
    }

    public function placeConfirmationCall(
        object $attempt
    ): array {
        if (! $this->ready()) {
            throw new RuntimeException(
                'Twilio Voice não está configurado.'
            );
        }

        $to = $this->toE164(
            (string) ($attempt->recipient ?? '')
        );

        if (! $to) {
            throw new RuntimeException(
                'Telefone do paciente inválido para ligação.'
            );
        }

        $answerUrl = route(
            'voice.twilio.answer',
            ['attempt' => $attempt->id],
            true
        );

        $statusUrl = route(
            'voice.twilio.status',
            ['attempt' => $attempt->id],
            true
        );

        $response = Http::asForm()
            ->withBasicAuth(
                $this->accountSid(),
                $this->authToken()
            )
            ->connectTimeout(10)
            ->timeout(20)
            ->post(
                'https://api.twilio.com/2010-04-01/Accounts/'
                    .$this->accountSid()
                    .'/Calls.json',
                [
                    'To' => $to,
                    'From' => $this->fromNumber(),
                    'Url' => $answerUrl,
                    'Method' => 'POST',
                    'StatusCallback' => $statusUrl,
                    'StatusCallbackMethod' => 'POST',
                    'Timeout' => (int) config(
                        'services.voice.twilio.ring_timeout',
                        25
                    ),
                ]
            );

        if (! $response->successful()) {
            throw new RuntimeException(
                'Twilio rejeitou a ligação: '
                .$response->status()
                .' '
                .mb_substr(
                    (string) $response->body(),
                    0,
                    500
                )
            );
        }

        $sid = (string) $response->json('sid');

        if ($sid === '') {
            throw new RuntimeException(
                'Twilio não retornou CallSid.'
            );
        }

        return [
            'external_id' => $sid,
            'status' => (string) (
                $response->json('status')
                ?: 'queued'
            ),
        ];
    }

    private function accountSid(): string
    {
        return trim((string) config(
            'services.voice.twilio.account_sid'
        ));
    }

    private function authToken(): string
    {
        return trim((string) config(
            'services.voice.twilio.auth_token'
        ));
    }

    private function fromNumber(): string
    {
        return trim((string) config(
            'services.voice.twilio.from'
        ));
    }

    private function toE164(
        string $raw
    ): ?string {
        $digits = preg_replace(
            '/\D+/',
            '',
            $raw
        );

        if (! $digits) {
            return null;
        }

        if (str_starts_with($digits, '55')
            && in_array(
                strlen($digits),
                [12, 13],
                true
            )) {
            return '+'.$digits;
        }

        if (in_array(
            strlen($digits),
            [10, 11],
            true
        )) {
            return '+55'.$digits;
        }

        if (strlen($digits) >= 8
            && strlen($digits) <= 15) {
            return '+'.$digits;
        }

        return null;
    }
}
