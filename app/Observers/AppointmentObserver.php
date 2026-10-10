<?php
namespace App\Observers;

use App\Models\Appointment;
use App\Services\Enfas\WhatsAppAutomationEngine;
use Illuminate\Support\Facades\Schema;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        if (Schema::hasTable('wa_automations')) {
            app(WhatsAppAutomationEngine::class)->trigger('appointment_created',$appointment->id);
        }
    }

    public function updated(
        Appointment $appointment
    ): void {
        if (! Schema::hasTable(
            'wa_automations'
        )) {
            return;
        }

        $rescheduled =
            $appointment->wasChanged(
                'start_at'
            );

        if ($rescheduled) {
            app(
                WhatsAppAutomationEngine::class
            )->trigger(
                'appointment_rescheduled',
                $appointment->id
            );
        }

        if (! $appointment->wasChanged(
            'status'
        )) {
            return;
        }

        /*
         * Um reagendamento que também deixa o horário confirmado
         * representa uma única intenção de comunicação. Evitamos
         * disparar "reagendado" e "confirmado" na mesma operação.
         */
        if ($rescheduled
            && $appointment->status === 'confirmed') {
            return;
        }

        $trigger = match (
            $appointment->status
        ) {
            'confirmed' =>
                'appointment_confirmed',
            'cancelled' =>
                'appointment_cancelled',
            'completed' =>
                'appointment_completed',
            default => null,
        };

        if ($trigger) {
            app(
                WhatsAppAutomationEngine::class
            )->trigger(
                $trigger,
                $appointment->id
            );
        }
    }
}
