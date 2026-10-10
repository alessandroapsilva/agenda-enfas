<?php

namespace App\Services\Enfas;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PatientNotificationService
{
    public function __construct(
        private MetaWhatsAppService $meta,
        private WhatsAppDispatchPolicy $policy
    ) {
    }

    public function confirmed(int $appointmentId, string $phone): void
    {
        $a = $this->data($appointmentId);
        $text = "✅ *Agendamento confirmado!*\n\n"
            ."Olá, {$a->patient_name} 😃\n"
            ."Seu atendimento foi confirmado com sucesso.\n\n"
            ."📋 *Procedimento:* {$a->service_name}\n"
            ."🥼 *Profissional:* {$a->professional_name}\n"
            ."📅 *Data:* ".Carbon::parse($a->start_at)->format('d/m/Y')."\n"
            ."⏰ *Horário:* ".Carbon::parse($a->start_at)->format('H:i')."\n"
            .($a->location_name ? "📍 *Local:* {$a->location_name}\n" : '')
            ."\n*Orientações importantes*\n"
            ."• Compareça com ".((int)($a->arrival_minutes ?: 15))." minutos de antecedência.\n"
            .($a->required_documents ? "• {$a->required_documents}\n" : "• Leve documento com foto.\n")
            .($a->preparation_instructions ? "• {$a->preparation_instructions}\n" : '')
            ."\n🔗 *Sua jornada ENFAS:*\n"
            .route('patient-journey.show', $a->public_token)."\n\n"
            ."Nesse link você pode consultar orientações, adicionar ao calendário e fazer check-in.\n\n"
            ."Qualquer dúvida, nossa equipe está à disposição. 💙\n"
            ."*Enfermagem Alessandro Silva*";

        $this->meta->sendTextMessage(
            $phone,
            $text,
            $appointmentId,
            $a->patient_id,
            $this->policy->journeyDedupeKey(
                $a,
                'confirmed'
            )
        );
    }

    public function cancelled(int $appointmentId, string $phone): void
    {
        $a = $this->data($appointmentId);

        $this->meta->sendTextMessage(
            $phone,
            "✅ *Cancelamento registrado*\n\n"
            ."Olá, {$a->patient_name}. O cancelamento do atendimento de *{$a->service_name}* foi registrado com sucesso.\n\n"
            ."Se desejar, nossa equipe pode ajudar com um novo agendamento. 💙\n"
            ."*Enfermagem Alessandro Silva*",
            $appointmentId,
            $a->patient_id,
            $this->policy->journeyDedupeKey(
                $a,
                'cancelled'
            )
        );
    }

    public function askPeriod(int $appointmentId, string $phone): void
    {
        $a = $this->data($appointmentId);

        $this->meta->sendInteractiveButtons(
            $phone,
            "🔄 *Vamos encontrar um novo horário*\n\n{$a->patient_name}, qual período você prefere para o seu atendimento?",
            [
                ['id' => 'PERIOD:morning:'.$a->code, 'title' => '☀️ Manhã'],
                ['id' => 'PERIOD:afternoon:'.$a->code, 'title' => '🌤️ Tarde'],
                ['id' => 'PERIOD:evening:'.$a->code, 'title' => '🌙 Noite'],
            ],
            $appointmentId,
            $a->patient_id,
            $this->policy->interactionDedupeKey(
                $a,
                'reschedule-period'
            ),
            'Enfermagem Alessandro Silva'
        );
    }

    public function offerSlots(int $appointmentId, string $phone, array $slots): void
    {
        $a = $this->data($appointmentId);

        if ($slots === []) {
            $this->meta->sendTextMessage(
                $phone,
                "Não encontrei horários nesse período no momento. Nossa equipe foi avisada e pode ajudar você a encontrar outra opção.",
                $appointmentId,
                $a->patient_id,
                $this->policy->interactionDedupeKey(
                    $a,
                    'reschedule-no-slots'
                )
            );
            return;
        }

        $buttons = [];
        $lines = [];

        foreach (array_slice($slots, 0, 3) as $index => $slot) {
            $n = $index + 1;
            $when = Carbon::parse($slot['start']);
            $lines[] = "{$n}️⃣ ".$when->translatedFormat('D, d/m')." às ".$when->format('H:i');
            $buttons[] = [
                'id' => 'SLOT:'.$n.':'.$a->code,
                'title' => $when->format('d/m H:i'),
            ];
        }

        $slotFingerprint = substr(
            sha1(
                json_encode(
                    array_map(
                        fn ($slot) =>
                            (string) ($slot['start'] ?? ''),
                        array_slice($slots, 0, 3)
                    )
                )
            ),
            0,
            16
        );

        $this->meta->sendInteractiveButtons(
            $phone,
            "📅 *Horários disponíveis com {$a->professional_name}*\n\n".implode("\n", $lines)."\n\nEscolha uma opção:",
            $buttons,
            $appointmentId,
            $a->patient_id,
            $this->policy->interactionDedupeKey(
                $a,
                'reschedule-slots',
                $slotFingerprint
            ),
            'Os horários são validados novamente na confirmação.'
        );
    }

    public function rescheduled(int $appointmentId, string $phone): void
    {
        $a = $this->data($appointmentId);

        $this->meta->sendTextMessage(
            $phone,
            "✅ *Reagendamento confirmado!*\n\n"
            ."📋 {$a->service_name}\n"
            ."🥼 {$a->professional_name}\n"
            ."📅 ".Carbon::parse($a->start_at)->format('d/m/Y')."\n"
            ."⏰ ".Carbon::parse($a->start_at)->format('H:i')."\n\n"
            ."Seu novo horário já está reservado. O profissional também foi avisado. 💙\n\n"
            ."🔗 Acompanhe sua jornada:\n"
            .route('patient-journey.show', $a->public_token),
            $appointmentId,
            $a->patient_id,
            $this->policy->journeyDedupeKey(
                $a,
                'rescheduled'
            )
        );
    }

    public function contactPreferenceChanged(
        int $patientId,
        string $phone,
        bool $enabled
    ): void {
        $text = $enabled
            ? "Preferência atualizada. Você voltou a autorizar comunicações automáticas da ENFAS Agenda sobre seus agendamentos. Se quiser interromper novamente, responda PARAR."
            : "Preferência registrada. Não enviaremos novas comunicações automáticas da ENFAS Agenda. Se quiser voltar a receber, responda ATIVAR.";

        $this->meta->sendTextMessage(
            $phone,
            $text,
            null,
            $patientId,
            'contact-preference:'
                .($enabled ? 'on' : 'off')
                .':'
                .$patientId
                .':'
                .now()->format('YmdH')
        );
    }

    private function data(int $appointmentId): object
    {
        $q = DB::table('appointments')
            ->join('patients', 'patients.id', '=', 'appointments.patient_id')
            ->join('professionals', 'professionals.id', '=', 'appointments.professional_id')
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->where('appointments.id', $appointmentId);

        if (\Illuminate\Support\Facades\Schema::hasTable('locations')
            && \Illuminate\Support\Facades\Schema::hasColumn('appointments', 'location_id')) {
            $q->leftJoin('locations', 'locations.id', '=', 'appointments.location_id');
        }

        return $q->first([
            'appointments.*',
            'patients.name as patient_name',
            'professionals.name as professional_name',
            'services.name as service_name',
            'services.arrival_minutes',
            'services.required_documents',
            'services.preparation_instructions',
            DB::raw(\Illuminate\Support\Facades\Schema::hasTable('locations')
                && \Illuminate\Support\Facades\Schema::hasColumn('appointments', 'location_id')
                ? 'COALESCE(locations.name, "") as location_name'
                : '"" as location_name'),
        ]);
    }
}
