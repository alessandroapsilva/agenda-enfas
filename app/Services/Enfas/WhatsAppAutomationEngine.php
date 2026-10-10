<?php

namespace App\Services\Enfas;

use App\Jobs\SendAppointmentWhatsApp;
use App\Models\WaAutomation;
use App\Models\WaMessage;
use Illuminate\Support\Facades\DB;

class WhatsAppAutomationEngine
{
    public function __construct(
        private readonly WhatsAppDispatchPolicy $policy
    ) {
    }

    public function trigger(
        string $event,
        int $appointmentId
    ): void {
        $appointment = DB::table(
            'appointments'
        )
            ->where(
                'id',
                $appointmentId
            )
            ->first();

        if (! $appointment) {
            return;
        }

        $rules = WaAutomation::with('template')
            ->where(
                'is_active',
                true
            )
            ->where(
                'trigger_event',
                $event
            )
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            $template = $rule->template;

            if (! $template
                || $template->status !== 'APPROVED'
                || ! (bool) ($template->is_active ?? true)
                || $template->archived_at !== null) {
                continue;
            }

            if ($rule->service_id
                && (int) $rule->service_id
                    !== (int) $appointment->service_id) {
                continue;
            }

            $purpose = (string) (
                $template->purpose
                ?: 'general'
            );

            if (! $this->policy
                ->automatedMessageAllowed(
                    $appointmentId,
                    $purpose
                )) {
                continue;
            }

            $offset = (int) (
                $rule->offset_minutes
                ?? 0
            );

            $dedupe = $this->policy
                ->canonicalDedupeKey(
                    $appointment,
                    $purpose,
                    $event,
                    $offset
                );

            $exact = WaMessage::query()
                ->where(
                    'dedupe_key',
                    $dedupe
                )
                ->first();

            if ($this->policy->blocksRetry(
                $exact,
                (int) $rule->template_id
            )) {
                continue;
            }

            $equivalent = $this->policy
                ->existingEquivalentMessage(
                    $appointment,
                    $purpose,
                    $event,
                    $offset
                );

            if ($equivalent) {
                continue;
            }

            SendAppointmentWhatsApp::dispatch(
                $appointmentId,
                (int) $rule->template_id,
                (int) $rule->id,
                $dedupe
            );
        }
    }
}
