<?php

namespace App\Services\Enfas;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfessionalNotificationService
{
    public function __construct(private MetaWhatsAppService $meta)
    {
    }

    public function appointmentChanged(int $appointmentId, string $event): void
    {
        $a = DB::table('appointments')
            ->join('patients', 'patients.id', '=', 'appointments.patient_id')
            ->join('professionals', 'professionals.id', '=', 'appointments.professional_id')
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->where('appointments.id', $appointmentId)
            ->first([
                'appointments.*',
                'patients.name as patient_name',
                'professionals.name as professional_name',
                'professionals.phone as professional_phone',
                'professionals.whatsapp_notifications_enabled',
                'services.name as service_name',
            ]);

        if (! $a || ! $a->professional_phone || ! $a->whatsapp_notifications_enabled) {
            return;
        }

        $headline = match ($event) {
            'confirmed' => '✅ Paciente confirmou',
            'cancelled' => '❌ Agendamento cancelado',
            'rescheduled' => '🔄 Agendamento reagendado',
            default => '📅 Atualização de agendamento',
        };

        $text = "{$headline}\n\n"
            ."👤 *Paciente:* {$a->patient_name}\n"
            ."📋 *Atendimento:* {$a->service_name}\n"
            ."📅 *Data:* ".Carbon::parse($a->start_at)->format('d/m/Y')."\n"
            ."⏰ *Horário:* ".Carbon::parse($a->start_at)->format('H:i')."\n"
            ."🔖 *Código:* {$a->code}";

        $this->meta->sendTextMessage(
            $a->professional_phone,
            $text,
            $appointmentId,
            null,
            'professional:'.$event.':'.$appointmentId.':'.Carbon::parse($a->start_at)->format('YmdHi')
        );
    }
}
