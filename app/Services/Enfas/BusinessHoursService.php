<?php

namespace App\Services\Enfas;

use App\Models\AppSetting;
use Carbon\Carbon;

class BusinessHoursService
{
    public function schedule(): array
    {
        $start = (string) AppSetting::getValue('agenda', 'day_start', '07:00');
        $end = (string) AppSetting::getValue('agenda', 'day_end', '20:00');

        $defaults = [];
        foreach (range(0, 6) as $day) {
            $defaults[$day] = [
                'enabled' => true,
                'start' => $start,
                'end' => $end,
                'break_start' => null,
                'break_end' => null,
            ];
        }

        $raw = AppSetting::getValue('system', 'business_hours');
        if (! is_string($raw) || trim($raw) === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $defaults;
        }

        foreach (range(0, 6) as $day) {
            $row = $decoded[(string) $day] ?? $decoded[$day] ?? null;
            if (! is_array($row)) {
                continue;
            }

            $defaults[$day] = [
                'enabled' => (bool) ($row['enabled'] ?? true),
                'start' => (string) ($row['start'] ?? $start),
                'end' => (string) ($row['end'] ?? $end),
                'break_start' => filled($row['break_start'] ?? null)
                    ? (string) $row['break_start']
                    : null,
                'break_end' => filled($row['break_end'] ?? null)
                    ? (string) $row['break_end']
                    : null,
            ];
        }

        return $defaults;
    }

    public function windowFor(Carbon $date): ?array
    {
        $row = $this->schedule()[$date->dayOfWeek] ?? null;

        if (! $row || ! $row['enabled']) {
            return null;
        }

        return $row;
    }

    public function constrain(array $windows, Carbon $date): array
    {
        $business = $this->windowFor($date);
        if (! $business) {
            return [];
        }

        $result = [];

        foreach ($windows as $window) {
            $start = max(
                $this->minutes((string) $window['start']),
                $this->minutes((string) $business['start'])
            );

            $end = min(
                $this->minutes((string) $window['end']),
                $this->minutes((string) $business['end'])
            );

            if ($end <= $start) {
                continue;
            }

            $breaks = [];

            foreach ([
                [$window['break_start'] ?? null, $window['break_end'] ?? null],
                [$business['break_start'] ?? null, $business['break_end'] ?? null],
            ] as [$breakStart, $breakEnd]) {
                if (! $breakStart || ! $breakEnd) {
                    continue;
                }

                $bs = max($start, $this->minutes((string) $breakStart));
                $be = min($end, $this->minutes((string) $breakEnd));

                if ($be > $bs) {
                    $breaks[] = [$bs, $be];
                }
            }

            usort($breaks, fn ($a, $b) => $a[0] <=> $b[0]);

            $cursor = $start;
            foreach ($breaks as [$bs, $be]) {
                if ($bs > $cursor) {
                    $result[] = $this->segment($window, $cursor, $bs);
                }
                $cursor = max($cursor, $be);
            }

            if ($cursor < $end) {
                $result[] = $this->segment($window, $cursor, $end);
            }
        }

        return array_values(array_filter(
            $result,
            fn ($row) => $this->minutes($row['end']) > $this->minutes($row['start'])
        ));
    }

    private function segment(array $window, int $start, int $end): array
    {
        return [
            'start' => $this->time($start),
            'end' => $this->time($end),
            'break_start' => null,
            'break_end' => null,
            'step' => $window['step'] ?? 30,
        ];
    }

    private function minutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));
        return ($hour * 60) + $minute;
    }

    private function time(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }
}
