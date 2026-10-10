<?php

namespace App\Services\Enfas;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PatientMultichannelNotificationService
{
    public function __construct(
        private CommunicationPolicyService $policy,
        private CommunicationDeliveryService $deliveries,
        private WhatsAppDispatchPolicy $contactPolicy
    ) {
    }

    public function appointmentChanged(
        int $appointmentId,
        string $event
    ): void {
        if (! $this->policy
            ->patientEventEnabled($event)) {
            return;
        }

        $appointment = $this->appointment(
            $appointmentId
        );

        if (! $appointment) {
            return;
        }

        if (! $this->contactPolicy
            ->patientAllowsContact(
                $appointmentId
            )) {
            return;
        }

        [
            $subject,
            $body,
            $sms,
        ] = $this->message(
            $appointment,
            $event
        );

        $slot = Carbon::parse(
            $appointment->start_at
        )->format('YmdHi');

        if (
            $this->policy
                ->patientEmailEnabled()
            && $appointment->patient_email
        ) {
            $this->deliveries->email(
                appointmentId:
                    $appointmentId,
                recipientType:
                    'patient',
                recipientId:
                    (int) $appointment->patient_id,
                recipient:
                    $appointment->patient_email,
                event:
                    $event,
                subject:
                    $subject,
                body:
                    $body,
                dedupeKey:
                    'patient:email:'
                    .$event
                    .':appointment:'
                    .$appointmentId
                    .':slot:'
                    .$slot
            );
        }

        if (
            $this->policy
                ->patientSmsEnabled()
            && $appointment->patient_phone
        ) {
            $this->deliveries->sms(
                appointmentId:
                    $appointmentId,
                recipientType:
                    'patient',
                recipientId:
                    (int) $appointment->patient_id,
                recipient:
                    $appointment->patient_phone,
                event:
                    $event,
                body:
                    $sms,
                dedupeKey:
                    'patient:sms:'
                    .$event
                    .':appointment:'
                    .$appointmentId
                    .':slot:'
                    .$slot
            );
        }
    }

    public function reminder(
        int $appointmentId,
        int $offsetMinutes,
        bool $dryRun = false
    ): array {
        if (! $this->policy
            ->patientEventEnabled(
                'reminder'
            )) {
            return [];
        }

        $appointment = $this->appointment(
            $appointmentId
        );

        if (! $appointment) {
            return [];
        }

        if (! $this->contactPolicy
            ->patientAllowsContact(
                $appointmentId
            )) {
            return [];
        }

        [
            $subject,
            $body,
            $sms,
        ] = $this->message(
            $appointment,
            'reminder'
        );

        $event =
            'reminder_'
            .$offsetMinutes;

        $slot = Carbon::parse(
            $appointment->start_at
        )->format('YmdHi');

        $planned = [];

        if (
            $this->policy
                ->patientEmailEnabled()
            && $appointment->patient_email
        ) {
            $planned[] = 'email';

            if (! $dryRun) {
                $this->deliveries->email(
                    appointmentId:
                        $appointmentId,
                    recipientType:
                        'patient',
                    recipientId:
                        (int) $appointment->patient_id,
                    recipient:
                        $appointment->patient_email,
                    event:
                        $event,
                    subject:
                        $subject,
                    body:
                        $body,
                    dedupeKey:
                        'patient:email:'
                        .$event
                        .':appointment:'
                        .$appointmentId
                        .':slot:'
                        .$slot
                );
            }
        }

        if (
            $this->policy
                ->patientSmsEnabled()
            && $appointment->patient_phone
        ) {
            $planned[] = 'sms';

            if (! $dryRun) {
                $this->deliveries->sms(
                    appointmentId:
                        $appointmentId,
                    recipientType:
                        'patient',
                    recipientId:
                        (int) $appointment->patient_id,
                    recipient:
                        $appointment->patient_phone,
                    event:
                        $event,
                    body:
                        $sms,
                    dedupeKey:
                        'patient:sms:'
                        .$event
                        .':appointment:'
                        .$appointmentId
                        .':slot:'
                        .$slot
                );
            }
        }

        return $planned;
    }

    private function appointment(
        int $appointmentId
    ): ?object {
        return DB::table(
            'appointments as a'
        )
            ->join(
                'patients as p',
                'p.id',
                '=',
                'a.patient_id'
            )
            ->join(
                'professionals as pr',
                'pr.id',
                '=',
                'a.professional_id'
            )
            ->join(
                'services as s',
                's.id',
                '=',
                'a.service_id'
            )
            ->leftJoin(
                'locations as l',
                'l.id',
                '=',
                'a.location_id'
            )
            ->where(
                'a.id',
                $appointmentId
            )
            ->first([
                'a.id',
                'a.code',
                'a.public_token',
                'a.patient_id',
                'a.start_at',
                'a.status',
                'p.name as patient_name',
                'p.phone as patient_phone',
                'p.email as patient_email',
                'pr.name as professional_name',
                's.name as service_name',
                'l.name as location_name',
            ]);
    }

    private function message(
        object $a,
        string $event
    ): array {
        $when = Carbon::parse(
            $a->start_at
        );

        $title = match ($event) {
            'created' =>
                'Agendamento registrado',
            'confirmed' =>
                'Presença confirmada',
            'cancelled' =>
                'Agendamento cancelado',
            'rescheduled' =>
                'Novo horário do agendamento',
            'reminder' =>
                'Lembrete do seu agendamento',
            default =>
                'Atualização do agendamento',
        };

        $intro = match ($event) {
            'created' =>
                'Seu agendamento foi registrado.',
            'confirmed' =>
                'Sua presença está confirmada.',
            'cancelled' =>
                'Seu agendamento foi cancelado.',
            'rescheduled' =>
                'Seu agendamento recebeu um novo horário.',
            'reminder' =>
                'Este é um lembrete do seu próximo agendamento.',
            default =>
                'Há uma atualização no seu agendamento.',
        };

        $body =
            $intro
            ."\n\n"
            .'Paciente: '
            .$a->patient_name
            ."\n"
            .'Serviço: '
            .$a->service_name
            ."\n"
            .'Profissional: '
            .$a->professional_name
            ."\n"
            .'Data: '
            .$when->format('d/m/Y')
            ."\n"
            .'Horário: '
            .$when->format('H:i')
            .(
                $a->location_name
                    ? "\nLocal: "
                        .$a->location_name
                    : ''
            )
            ."\n"
            .'Código: '
            .$a->code
            ."\n\n"
            .'ENFAS Agenda';

        $sms =
            'ENFAS Agenda: '
            .$title
            .'. '
            .$a->service_name
            .' em '
            .$when->format('d/m')
            .' às '
            .$when->format('H:i')
            .'. Cod. '
            .$a->code
            .'.';

        return [
            $title.' · ENFAS Agenda',
            $body,
            $sms,
        ];
    }
}
