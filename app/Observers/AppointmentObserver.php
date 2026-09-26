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

    public function updated(Appointment $appointment): void
    {
        if (! Schema::hasTable('wa_automations')) return;

        if ($appointment->wasChanged('start_at')) {
            app(WhatsAppAutomationEngine::class)->trigger('appointment_rescheduled',$appointment->id);
        }

        if ($appointment->wasChanged('status')) {
            $trigger = match($appointment->status) {
                'confirmed'=>'appointment_confirmed',
                'cancelled'=>'appointment_cancelled',
                'completed'=>'appointment_completed',
                default=>null,
            };
            if ($trigger) app(WhatsAppAutomationEngine::class)->trigger($trigger,$appointment->id);
        }
    }
}
