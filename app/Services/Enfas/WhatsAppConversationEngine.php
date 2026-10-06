<?php

namespace App\Services\Enfas;

use App\Models\Appointment;
use App\Models\WaConversation;
use App\Models\WaMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class WhatsAppConversationEngine
{
    public function __construct(
        private AvailabilityService $availability,
        private PatientNotificationService $patientNotifications,
        private ProfessionalNotificationService $professionalNotifications,
        private WhatsAppAutomationEngine $automation,
        private WaitlistService $waitlist,
    ) {
    }

    public function handle(array $incoming): void
    {
        $metaId = $incoming['id'] ?? null;

        if ($metaId && WaMessage::where('meta_message_id', $metaId)->exists()) {
            return;
        }

        $phone = (string) ($incoming['from'] ?? '');
        $payload = data_get($incoming, 'button.payload')
            ?? data_get($incoming, 'interactive.button_reply.id');
        $text = data_get($incoming, 'button.text')
            ?? data_get($incoming, 'interactive.button_reply.title')
            ?? data_get($incoming, 'text.body');

        if ($phone === '') {
            return;
        }

        $conversation = WaConversation::query()
            ->where('phone', $phone)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (! $conversation) {
            $lastKnown = WaMessage::query()
                ->where('recipient', $phone)
                ->where(function ($q) {
                    $q->whereNotNull('patient_id')
                        ->orWhereNotNull('appointment_id');
                })
                ->latest('id')
                ->first();

            $conversation = WaConversation::create([
                'phone' => $phone,
                'patient_id' => $lastKnown?->patient_id,
                'appointment_id' => $lastKnown?->appointment_id,
                'state' => 'IDLE',
                'status' => 'active',
                'mode' => 'bot',
                'unread_count' => 1,
                'context' => [],
                'last_message_at' => now(),
                'first_inbound_at' => now(),
                'last_inbound_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);
        } else {
            $conversation->update([
                'last_message_at' => now(),
                'first_inbound_at' => $conversation->first_inbound_at ?: now(),
                'last_inbound_at' => now(),
                'unread_count' => ((int) $conversation->unread_count) + 1,
                'expires_at' => now()->addHours(24),
            ]);
        }

        $message = WaMessage::create([
            'appointment_id' => $conversation->appointment_id,
            'patient_id' => $conversation->patient_id,
            'direction' => 'inbound',
            'message_type' => $incoming['type'] ?? 'unknown',
            'meta_message_id' => $metaId,
            'status' => 'received',
            'recipient' => $phone,
            'body' => $text,
            'payload' => $incoming,
        ]);

        // Atendimento humano sempre tem prioridade sobre o robô.
        if ($conversation->mode === 'human') {
            return;
        }

        if (! $payload || ! str_contains($payload, ':')) {
            return;
        }

        $parts = explode(':', $payload);
        $action = strtoupper((string) ($parts[0] ?? ''));

        if ($action === 'WAITLIST_ACCEPT') {
            $entryId = (int) ($parts[1] ?? 0);

            if ($entryId <= 0) {
                return;
            }

            $appointmentModel = $this->waitlist->accept($entryId);

            $message->update([
                'appointment_id' => $appointmentModel->id,
                'patient_id' => $appointmentModel->patient_id,
            ]);

            $conversation->update([
                'appointment_id' => $appointmentModel->id,
                'patient_id' => $appointmentModel->patient_id,
                'state' => 'IDLE',
                'status' => 'completed',
                'last_message_at' => now(),
                'closed_at' => now(),
            ]);

            $this->patientNotifications->confirmed($appointmentModel->id, $phone);
            $this->professionalNotifications->appointmentChanged($appointmentModel->id, 'confirmed');

            return;
        }

        if ($action === 'WAITLIST_DECLINE') {
            $entryId = (int) ($parts[1] ?? 0);

            if ($entryId > 0) {
                $this->waitlist->decline($entryId);
            }

            return;
        }

        $code = (string) end($parts);
        $appointment = DB::table('appointments')->where('code', $code)->first();

        if (! $appointment) {
            return;
        }

        $message->update([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
        ]);

        $conversation->update([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'last_message_at' => now(),
            'last_inbound_at' => now(),
        ]);

        match ($action) {
            'CONFIRM' => $this->confirm($appointment, $phone),
            'CANCEL' => $this->cancel($appointment, $phone),
            'RESCHEDULE' => $this->beginReschedule($appointment, $phone),
            'PERIOD' => $this->choosePeriod($appointment, $phone, $parts[1] ?? null),
            'SLOT' => $this->chooseSlot($appointment, $phone, (int) ($parts[1] ?? 0)),
            default => null,
        };
    }

    private function confirm(object $appointment, string $phone): void
    {
        DB::table('appointments')->where('id', $appointment->id)->update([
            'status' => 'confirmed',
            'confirmation_status' => 'confirmed',
            'confirmed_at' => now(),
            'updated_at' => now(),
        ]);

        $this->event($appointment->id, 'whatsapp_confirmed', 'Paciente confirmou pelo WhatsApp');
        $this->patientNotifications->confirmed($appointment->id, $phone);
        $this->professionalNotifications->appointmentChanged($appointment->id, 'confirmed');
        $this->closeConversation($phone, $appointment->id);
    }

    private function cancel(object $appointment, string $phone): void
    {
        DB::table('appointments')->where('id', $appointment->id)->update([
            'status' => 'cancelled',
            'confirmation_status' => 'cancelled',
            'cancelled_at' => now(),
            'updated_at' => now(),
        ]);

        $this->event($appointment->id, 'whatsapp_cancelled', 'Paciente cancelou pelo WhatsApp');
        $this->patientNotifications->cancelled($appointment->id, $phone);
        $this->professionalNotifications->appointmentChanged($appointment->id, 'cancelled');

        $cancelled = Appointment::find($appointment->id);

        if ($cancelled) {
            try {
                $this->waitlist->offerFreedSlot($cancelled);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->closeConversation($phone, $appointment->id);
    }

    private function beginReschedule(object $appointment, string $phone): void
    {
        $this->conversation($phone, $appointment, 'CHOOSING_PERIOD', []);
        $this->event($appointment->id, 'whatsapp_reschedule_requested', 'Paciente solicitou reagendamento');
        $this->patientNotifications->askPeriod($appointment->id, $phone);
    }

    private function choosePeriod(object $appointment, string $phone, ?string $period): void
    {
        if (! in_array($period, ['morning', 'afternoon', 'evening'], true)) {
            return;
        }

        $slots = $this->availability->nextSlots(
            $appointment->professional_id,
            $appointment->service_id,
            now()->addMinutes(5),
            $period,
            3,
            $appointment->id
        );

        $conversation = $this->conversation($phone, $appointment, 'CHOOSING_SLOT', [
            'period' => $period,
            'slots' => $slots,
        ]);

        $conversation->touch();
        $this->patientNotifications->offerSlots($appointment->id, $phone, $slots);
    }

    private function chooseSlot(object $appointment, string $phone, int $position): void
    {
        $conversation = WaConversation::query()
            ->where('phone', $phone)
            ->where('appointment_id', $appointment->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        $slots = $conversation?->context['slots'] ?? [];
        $slot = $slots[$position - 1] ?? null;

        if (! $slot) {
            return;
        }

        $start = Carbon::parse($slot['start']);
        $end = Carbon::parse($slot['end']);

        $hold = $this->availability->hold(
            $appointment->professional_id,
            $start,
            $end,
            $appointment->id
        );

        DB::transaction(function () use ($appointment, $start, $end, $hold) {
            DB::table('appointments')->where('id', $appointment->id)->update([
                'start_at' => $start,
                'end_at' => $end,
                'status' => 'confirmed',
                'confirmation_status' => 'confirmed',
                'confirmed_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('slot_reservations')->where('id', $hold->id)->update([
                'status' => 'consumed',
                'updated_at' => now(),
            ]);
        });

        $this->event($appointment->id, 'whatsapp_rescheduled', 'Paciente reagendou pelo WhatsApp');
        $this->patientNotifications->rescheduled($appointment->id, $phone);
        $this->professionalNotifications->appointmentChanged($appointment->id, 'rescheduled');
        $this->closeConversation($phone, $appointment->id);
    }

    private function conversation(string $phone, object $appointment, string $state, array $context): WaConversation
    {
        return WaConversation::updateOrCreate(
            [
                'phone' => $phone,
                'appointment_id' => $appointment->id,
                'status' => 'active',
            ],
            [
                'patient_id' => $appointment->patient_id,
                'state' => $state,
                'context' => $context,
                'last_message_at' => now(),
                'expires_at' => now()->addHours(24),
            ]
        );
    }

    private function closeConversation(string $phone, int $appointmentId): void
    {
        if (! Schema::hasTable('wa_conversations')) {
            return;
        }

        WaConversation::where('phone', $phone)
            ->where('appointment_id', $appointmentId)
            ->where('status', 'active')
            ->update([
                'status' => 'completed',
                'state' => 'IDLE',
                'last_message_at' => now(),
            ]);
    }

    private function event(int $appointmentId, string $type, string $title): void
    {
        if (! Schema::hasTable('appointment_events')) {
            return;
        }

        DB::table('appointment_events')->insert([
            'appointment_id' => $appointmentId,
            'user_id' => null,
            'event_type' => $type,
            'title' => $title,
            'description' => null,
            'metadata' => null,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
