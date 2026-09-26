<?php

namespace App\Services\Enfas;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class AvailabilityService
{
    public function nextSlots(
        int $professionalId,
        int $serviceId,
        ?Carbon $from = null,
        ?string $period = null,
        int $limit = 3,
        ?int $excludeAppointmentId = null,
        int $daysAhead = 45
    ): array {
        $professional = DB::table('professionals')->where('id', $professionalId)->where('is_active', true)->first();
        $service = DB::table('services')->where('id', $serviceId)->where('is_active', true)->first();

        if (! $professional || ! $service) {
            return [];
        }

        $duration = max(5, (int) ($service->duration_minutes ?: 30));
        $step = max(5, (int) ($professional->slot_interval ?: $duration));
        $cursorDate = ($from ?: now())->copy()->startOfDay();
        $now = now();
        $slots = [];

        for ($day = 0; $day <= $daysAhead && count($slots) < $limit; $day++) {
            $date = $cursorDate->copy()->addDays($day);

            foreach ($this->windowsFor($professional, $date) as $window) {
                $start = $this->combine($date, $window['start']);
                $end = $this->combine($date, $window['end']);
                $breakStart = $window['break_start'] ? $this->combine($date, $window['break_start']) : null;
                $breakEnd = $window['break_end'] ? $this->combine($date, $window['break_end']) : null;

                for ($candidate = $start->copy(); $candidate->copy()->addMinutes($duration)->lte($end); $candidate->addMinutes($step)) {
                    $candidateEnd = $candidate->copy()->addMinutes($duration);

                    if ($candidate->lte($now)) {
                        continue;
                    }

                    if (! $this->matchesPeriod($candidate, $period)) {
                        continue;
                    }

                    if ($breakStart && $breakEnd && $candidate->lt($breakEnd) && $candidateEnd->gt($breakStart)) {
                        continue;
                    }

                    if (! $this->isAvailable($professionalId, $candidate, $candidateEnd, $excludeAppointmentId)) {
                        continue;
                    }

                    $slots[] = [
                        'start' => $candidate->toDateTimeString(),
                        'end' => $candidateEnd->toDateTimeString(),
                        'label' => $candidate->format('d/m/Y H:i'),
                    ];

                    if (count($slots) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        return $slots;
    }

    public function isAvailable(
        int $professionalId,
        Carbon $start,
        Carbon $end,
        ?int $excludeAppointmentId = null
    ): bool {
        $appointments = DB::table('appointments')
            ->where('professional_id', $professionalId)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start);

        if ($excludeAppointmentId) {
            $appointments->where('id', '!=', $excludeAppointmentId);
        }

        if ($appointments->exists()) {
            return false;
        }

        if (Schema::hasTable('professional_blocks')) {
            $blocked = DB::table('professional_blocks')
                ->where('professional_id', $professionalId)
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->exists();

            if ($blocked) {
                return false;
            }
        }

        if (Schema::hasTable('slot_reservations')) {
            $holds = DB::table('slot_reservations')
                ->where('professional_id', $professionalId)
                ->where('status', 'held')
                ->where('expires_at', '>', now())
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start);

            if ($holds->exists()) {
                return false;
            }
        }

        return true;
    }

    public function hold(
        int $professionalId,
        Carbon $start,
        Carbon $end,
        ?int $appointmentId = null,
        int $minutes = 5
    ): object {
        return DB::transaction(function () use ($professionalId, $start, $end, $appointmentId, $minutes) {
            DB::table('slot_reservations')
                ->where('status', 'held')
                ->where('expires_at', '<=', now())
                ->update(['status' => 'expired', 'updated_at' => now()]);

            if (! $this->isAvailable($professionalId, $start, $end, $appointmentId)) {
                throw new RuntimeException('Esse horário acabou de ficar indisponível. Escolha outro horário.');
            }

            $token = (string) Str::uuid();

            $id = DB::table('slot_reservations')->insertGetId([
                'token' => $token,
                'professional_id' => $professionalId,
                'appointment_id' => $appointmentId,
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => 'held',
                'expires_at' => now()->addMinutes($minutes),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('slot_reservations')->where('id', $id)->first();
        });
    }

    private function windowsFor(object $professional, Carbon $date): array
    {
        if (Schema::hasTable('professional_availabilities')) {
            $rows = DB::table('professional_availabilities')
                ->where('professional_id', $professional->id)
                ->where('day_of_week', $date->dayOfWeek)
                ->where('is_active', true)
                ->orderBy('start_time')
                ->get();

            if ($rows->isNotEmpty()) {
                return $rows->map(fn ($row) => [
                    'start' => $row->start_time,
                    'end' => $row->end_time,
                    'break_start' => $row->break_start,
                    'break_end' => $row->break_end,
                ])->all();
            }
        }

        $activeDays = is_array($professional->active_days ?? null)
            ? $professional->active_days
            : json_decode((string) ($professional->active_days ?? '[]'), true);

        if ($activeDays && ! in_array($date->dayOfWeek, array_map('intval', $activeDays), true)) {
            return [];
        }

        return [[
            'start' => $professional->work_start ?: '08:00:00',
            'end' => $professional->work_end ?: '18:00:00',
            'break_start' => null,
            'break_end' => null,
        ]];
    }

    private function combine(Carbon $date, string $time): Carbon
    {
        return Carbon::parse($date->format('Y-m-d').' '.$time, config('app.timezone'));
    }

    private function matchesPeriod(Carbon $time, ?string $period): bool
    {
        return match ($period) {
            'morning' => $time->hour >= 6 && $time->hour < 12,
            'afternoon' => $time->hour >= 12 && $time->hour < 18,
            'evening' => $time->hour >= 18 && $time->hour < 23,
            default => true,
        };
    }
}
