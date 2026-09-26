<?php

namespace App\Services\Enfas;

use App\Models\Appointment;
use App\Models\AppointmentSeries;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecurringAppointmentService
{
    public function __construct(private AvailabilityService $availability)
    {
    }

    public function create(array $data): AppointmentSeries
    {
        return DB::transaction(function () use ($data) {
            $series = AppointmentSeries::create([
                'patient_id' => $data['patient_id'],
                'professional_id' => $data['professional_id'],
                'service_id' => $data['service_id'],
                'frequency' => $data['frequency'],
                'interval' => $data['interval'] ?? 1,
                'week_days' => $data['week_days'] ?? null,
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'] ?? null,
                'max_occurrences' => $data['max_occurrences'] ?? null,
                'status' => 'active',
                'metadata' => $data['metadata'] ?? null,
            ]);

            $starts = $this->occurrences($data);

            foreach ($starts as $index => $start) {
                $duration = (int) DB::table('services')->where('id', $data['service_id'])->value('duration_minutes');
                $end = $start->copy()->addMinutes(max(5, $duration));

                if (! $this->availability->isAvailable($data['professional_id'], $start, $end)) {
                    throw new RuntimeException('Conflito encontrado em '.$start->format('d/m/Y H:i').'. A série não foi criada.');
                }

                Appointment::create([
                    'patient_id' => $data['patient_id'],
                    'professional_id' => $data['professional_id'],
                    'service_id' => $data['service_id'],
                    'series_id' => $series->id,
                    'series_position' => $index + 1,
                    'start_at' => $start,
                    'end_at' => $end,
                    'duration_minutes' => max(5, $duration),
                    'status' => 'awaiting_confirmation',
                    'confirmation_status' => 'pending',
                    'source' => 'recurrence',
                ]);
            }

            return $series;
        });
    }

    private function occurrences(array $data): array
    {
        $start = Carbon::parse($data['starts_on'].' '.$data['time']);
        $end = ! empty($data['ends_on']) ? Carbon::parse($data['ends_on'].' 23:59:59') : null;
        $max = (int) ($data['max_occurrences'] ?? 52);
        $max = max(1, min($max, 365));
        $interval = max(1, (int) ($data['interval'] ?? 1));
        $out = [];
        $cursor = $start->copy();

        while (count($out) < $max && (! $end || $cursor->lte($end))) {
            $include = true;

            if (($data['frequency'] ?? '') === 'weekly' && ! empty($data['week_days'])) {
                $include = in_array($cursor->dayOfWeek, array_map('intval', $data['week_days']), true);
            }

            if ($include) {
                $out[] = $cursor->copy();
            }

            $cursor = match ($data['frequency']) {
                'daily' => $cursor->addDays($interval),
                'weekly' => empty($data['week_days']) ? $cursor->addWeeks($interval) : $cursor->addDay(),
                'monthly' => $cursor->addMonthsNoOverflow($interval),
                default => throw new RuntimeException('Frequência de recorrência inválida.'),
            };
        }

        return $out;
    }
}
