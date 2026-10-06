<?php
namespace App\Services\Enfas;

use App\Jobs\SendAppointmentWhatsApp;
use App\Models\WaAutomation;
use App\Models\WaMessage;
use Illuminate\Support\Facades\DB;

class WhatsAppAutomationEngine
{
    public function trigger(string $event, int $appointmentId): void
    {
        $a = DB::table('appointments')->where('id',$appointmentId)->first();
        if (! $a) return;

        $rules = WaAutomation::with('template')
            ->where('is_active',true)
            ->where('trigger_event',$event)
            ->get();

        foreach ($rules as $rule) {
            if (! $rule->template || $rule->template->status !== 'APPROVED') continue;
            if ($rule->service_id && (int)$rule->service_id !== (int)$a->service_id) continue;

            $isConfirmation = $rule->template->purpose === 'confirmation';

            $dedupe = $isConfirmation
                ? 'auto:confirmation:event:'.$event.':appointment:'.$appointmentId
                : ($rule->send_once
                    ? 'auto:'.$rule->id.':appointment:'.$appointmentId
                    : null);

            if ($dedupe && WaMessage::where('dedupe_key',$dedupe)->exists()) continue;

            SendAppointmentWhatsApp::dispatch(
                $appointmentId,$rule->template_id,$rule->id,$dedupe
            );
        }
    }
}
