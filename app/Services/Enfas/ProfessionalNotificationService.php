<?php

namespace App\Services\Enfas;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProfessionalNotificationService
{
    public function __construct(
        private MetaWhatsAppService $meta,
        private CommunicationPolicyService $policy,
        private CommunicationDeliveryService $deliveries
    ) {
    }

    public function appointmentChanged(
        int $appointmentId,
        string $event
    ): void {
        if (! $this->policy
            ->professionalEventEnabled($event)) {
            return;
        }

        $a = DB::table(
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
                'a.professional_id',
                'a.start_at',
                'p.name as patient_name',
                'pr.name as professional_name',
                'pr.phone as professional_phone',
                'pr.email as professional_email',
                'pr.whatsapp_notifications_enabled',
                'pr.email_notifications_enabled',
                'pr.sms_notifications_enabled',
                's.name as service_name',
                'l.name as location_name',
            ]);

        if (! $a) {
            return;
        }

        [
            $headline,
            $subject,
            $text,
            $sms,
        ] = $this->message(
            $a,
            $event
        );

        $slot = Carbon::parse(
            $a->start_at
        )->format('YmdHi');

        if (
            $this->policy
                ->professionalWhatsAppEnabled()
            && $a->professional_phone
            && $a->whatsapp_notifications_enabled
        ) {
            try {
                $this->meta->sendTextMessage(
                    $a->professional_phone,
                    $text,
                    $appointmentId,
                    null,
                    'professional:whatsapp:'
                    .$event
                    .':appointment:'
                    .$appointmentId
                    .':slot:'
                    .$slot
                );
            } catch (Throwable $e) {
                report($e);
            }
        }

        if (
            $this->policy
                ->professionalEmailEnabled()
            && $a->professional_email
            && $a->email_notifications_enabled
        ) {
            $this->deliveries->email(
                appointmentId:
                    $appointmentId,
                recipientType:
                    'professional',
                recipientId:
                    (int) $a->professional_id,
                recipient:
                    $a->professional_email,
                event:
                    $event,
                subject:
                    $subject,
                body:
                    $text,
                dedupeKey:
                    'professional:email:'
                    .$event
                    .':appointment:'
                    .$appointmentId
                    .':slot:'
                    .$slot
            );
        }

        if (
            $this->policy
                ->professionalSmsEnabled()
            && $a->professional_phone
            && $a->sms_notifications_enabled
        ) {
            $this->deliveries->sms(
                appointmentId:
                    $appointmentId,
                recipientType:
                    'professional',
                recipientId:
                    (int) $a->professional_id,
                recipient:
                    $a->professional_phone,
                event:
                    $event,
                body:
                    $sms,
                dedupeKey:
                    'professional:sms:'
                    .$event
                    .':appointment:'
                    .$appointmentId
                    .':slot:'
                    .$slot
            );
        }
    }

    private function message(
        object $a,
        string $event
    ): array {
        $when = Carbon::parse(
            $a->start_at
        );

        $headline = match ($event) {
            'created' =>
                '📅 Novo agendamento',
            'confirmed' =>
                '✅ Paciente confirmou',
            'cancelled' =>
                '❌ Agendamento cancelado',
            'rescheduled' =>
                '🔄 Agendamento reagendado',
            default =>
                '📅 Atualização do agendamento',
        };

        $subject = match ($event) {
            'created' =>
                'Novo agendamento',
            'confirmed' =>
                'Paciente confirmou presença',
            'cancelled' =>
                'Agendamento cancelado',
            'rescheduled' =>
                'Agendamento reagendado',
            default =>
                'Atualização do agendamento',
        };

        $text =
            $headline
            ."\n\n"
            .'Paciente: '
            .$a->patient_name
            ."\n"
            .'Serviço: '
            .$a->service_name
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
            .$a->code;

        $sms =
            'ENFAS Agenda: '
            .$subject
            .'. '
            .$a->patient_name
            .' · '
            .$a->service_name
            .' · '
            .$when->format('d/m H:i')
            .'.';

        return [
            $headline,
            $subject.' · ENFAS Agenda',
            $text,
            $sms,
        ];
    }
}
