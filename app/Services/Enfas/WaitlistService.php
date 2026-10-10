<?php

namespace App\Services\Enfas;

use App\Models\Appointment;
use App\Models\WaitlistEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WaitlistService
{
    public function __construct(
        private AvailabilityService $availability,
        private MetaWhatsAppService $meta,
        private WhatsAppDispatchPolicy $dispatchPolicy
    ) {
    }

    public function offerFreedSlot(
        Appointment $appointment,
        ?int $excludeEntryId = null
    ): ?WaitlistEntry
    {
        $appointment->loadMissing(['service', 'professional']);

        $start = $appointment->start_at->copy();
        $end = $appointment->end_at->copy();

        $this->expireOldOffers();

        $candidate = WaitlistEntry::query()
            ->with('patient')
            ->where('status', 'waiting')
            ->where('service_id', $appointment->service_id)
            ->when(
                $excludeEntryId,
                fn ($q) => $q->where('id', '!=', $excludeEntryId)
            )
            ->where(function ($q) use ($appointment) {
                $q->whereNull('professional_id')
                    ->orWhere('professional_id', $appointment->professional_id);
            })
            ->where(function ($q) use ($appointment) {
                $q->whereNull('location_id')
                    ->orWhere('location_id', $appointment->location_id);
            })
            ->where(function ($q) use ($start) {
                $q->whereNull('earliest_date')
                    ->orWhereDate('earliest_date', '<=', $start->toDateString());
            })
            ->where(function ($q) use ($start) {
                $q->whereNull('latest_date')
                    ->orWhereDate('latest_date', '>=', $start->toDateString());
            })
            ->orderBy('created_at')
            ->get()
            ->first(function (WaitlistEntry $entry) use ($start) {
                return $entry->patient?->phone
                    && $this->dispatchPolicy
                        ->patientAllowsContactByPatientId(
                            (int) $entry->patient_id
                        )
                    && (! $entry->preferred_period
                        || $this->matchesPeriod(
                            $start,
                            $entry->preferred_period
                        ));
            });

        if (! $candidate) {
            return null;
        }

        if (! $this->availability->isAvailable(
            $appointment->professional_id,
            $start,
            $end,
            $appointment->id
        )) {
            return null;
        }

        $candidate->update([
            'professional_id' => $appointment->professional_id,
            'location_id' => $candidate->location_id ?: $appointment->location_id,
            'status' => 'offered',
            'offered_start_at' => $start,
            'offered_end_at' => $end,
            'offer_expires_at' => now()->addMinutes(15),
        ]);

        $body = "✨ *Surgiu um horário para você!*\n\n"
            ."📋 *Atendimento:* {$appointment->service->name}\n"
            ."🥼 *Profissional:* {$appointment->professional->name}\n"
            ."📅 *Data:* {$start->format('d/m/Y')}\n"
            ."⏰ *Horário:* {$start->format('H:i')}\n\n"
            ."Essa oferta fica disponível por 15 minutos e pode ser ocupada enquanto você decide. Deseja confirmar?";

        try {
            $this->meta->sendInteractiveButtons(
                $candidate->patient->phone,
                $body,
                [
                    ['id' => 'WAITLIST_ACCEPT:'.$candidate->id, 'title' => '✅ Aceitar horário'],
                    ['id' => 'WAITLIST_DECLINE:'.$candidate->id, 'title' => 'Agora não'],
                ],
                null,
                $candidate->patient_id,
                'waitlist-offer:'.$candidate->id.':'.$start->format('YmdHi'),
                'Lista de espera · Enfermagem Alessandro Silva'
            );
        } catch (\Throwable $e) {
            $candidate->update([
                'status' => 'waiting',
                'offered_start_at' => null,
                'offered_end_at' => null,
                'offer_expires_at' => null,
            ]);

            throw $e;
        }

        return $candidate;
    }

    public function accept(int $entryId): Appointment
    {
        return DB::transaction(function () use ($entryId) {
            /** @var WaitlistEntry $entry */
            $entry = WaitlistEntry::query()
                ->with(['patient','service','professional'])
                ->lockForUpdate()
                ->findOrFail($entryId);

            if ($entry->status !== 'offered'
                || ! $entry->offer_expires_at
                || $entry->offer_expires_at->isPast()
            ) {
                throw new RuntimeException('Essa oferta não está mais disponível.');
            }

            $professionalId = $entry->professional_id;

            if (! $professionalId) {
                throw new RuntimeException('A oferta perdeu o profissional vinculado.');
            }

            $start = $entry->offered_start_at->copy();
            $end = $entry->offered_end_at->copy();

            $hold = $this->availability->hold(
                $professionalId,
                $start,
                $end,
                null,
                2
            );

            $appointment = Appointment::create([
                'patient_id' => $entry->patient_id,
                'professional_id' => $professionalId,
                'service_id' => $entry->service_id,
                'location_id' => $entry->location_id,
                'start_at' => $start,
                'end_at' => $end,
                'duration_minutes' => $start->diffInMinutes($end),
                'status' => 'confirmed',
                'confirmation_status' => 'confirmed',
                'confirmation_channel' => 'whatsapp_waitlist',
                'confirmed_at' => now(),
                'source' => 'waitlist',
            ]);

            DB::table('slot_reservations')
                ->where('id', $hold->id)
                ->update([
                    'status' => 'consumed',
                    'appointment_id' => $appointment->id,
                    'updated_at' => now(),
                ]);

            $entry->update([
                'status' => 'accepted',
                'appointment_id' => $appointment->id,
            ]);

            return $appointment;
        });
    }

    public function decline(int $entryId): void
    {
        $entry = WaitlistEntry::query()
            ->with(['service','professional'])
            ->where('id', $entryId)
            ->where('status', 'offered')
            ->first();

        if (! $entry) {
            return;
        }

        $start = $entry->offered_start_at?->copy();
        $end = $entry->offered_end_at?->copy();
        $professionalId = $entry->professional_id;
        $serviceId = $entry->service_id;
        $locationId = $entry->location_id;

        $entry->update([
            'status' => 'waiting',
            'offered_start_at' => null,
            'offered_end_at' => null,
            'offer_expires_at' => null,
        ]);

        if (
            ! $start
            || ! $end
            || ! $professionalId
            || $start->isPast()
        ) {
            return;
        }

        $slot = new Appointment([
            'professional_id' => $professionalId,
            'service_id' => $serviceId,
            'location_id' => $locationId,
            'start_at' => $start,
            'end_at' => $end,
            'status' => 'cancelled',
        ]);

        try {
            $this->offerFreedSlot($slot, $entry->id);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function expireOldOffers(): void
    {
        WaitlistEntry::query()
            ->where('status', 'offered')
            ->whereNotNull('offer_expires_at')
            ->where('offer_expires_at', '<=', now())
            ->update([
                'status' => 'waiting',
                'offered_start_at' => null,
                'offered_end_at' => null,
                'offer_expires_at' => null,
                'updated_at' => now(),
            ]);
    }

    private function matchesPeriod(Carbon $time, string $period): bool
    {
        return match ($period) {
            'morning' => $time->hour >= 6 && $time->hour < 12,
            'afternoon' => $time->hour >= 12 && $time->hour < 18,
            'evening' => $time->hour >= 18 && $time->hour < 23,
            default => true,
        };
    }
}
