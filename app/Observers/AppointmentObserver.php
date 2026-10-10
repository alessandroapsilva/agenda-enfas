<?php
namespace App\Observers;

use App\Models\Appointment;
use App\Services\Enfas\PatientMultichannelNotificationService;
use App\Services\Enfas\ProfessionalNotificationService;
use App\Services\Enfas\WhatsAppAutomationEngine;
use Illuminate\Support\Facades\Schema;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        if (Schema::hasTable('wa_automations')) {
            app(
                WhatsAppAutomationEngine::class
            )->trigger(
                'appointment_created',
                $appointment->id
            );
        }

        app(
            PatientMultichannelNotificationService::class
        )->appointmentChanged(
            $appointment->id,
            'created'
        );

        app(
            ProfessionalNotificationService::class
        )->appointmentChanged(
            $appointment->id,
            'created'
        );
    }

    public function updated(
        Appointment $appointment
    ): void {
        $hasWhatsAppAutomations =
            Schema::hasTable(
                'wa_automations'
            );

        $rescheduled =
            $appointment->wasChanged(
                'start_at'
            );

        if ($rescheduled) {
            if ($hasWhatsAppAutomations) {
                app(
                    WhatsAppAutomationEngine::class
                )->trigger(
                    'appointment_rescheduled',
                    $appointment->id
                );
            }

            app(
                PatientMultichannelNotificationService::class
            )->appointmentChanged(
                $appointment->id,
                'rescheduled'
            );

            app(
                ProfessionalNotificationService::class
            )->appointmentChanged(
                $appointment->id,
                'rescheduled'
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
            if ($hasWhatsAppAutomations) {
                app(
                    WhatsAppAutomationEngine::class
                )->trigger(
                    $trigger,
                    $appointment->id
                );
            }

            $event = match (
                $appointment->status
            ) {
                'confirmed' => 'confirmed',
                'cancelled' => 'cancelled',
                default => null,
            };

            if ($event) {
                app(
                    PatientMultichannelNotificationService::class
                )->appointmentChanged(
                    $appointment->id,
                    $event
                );

                app(
                    ProfessionalNotificationService::class
                )->appointmentChanged(
                    $appointment->id,
                    $event
                );
            }
        }
    }
}
