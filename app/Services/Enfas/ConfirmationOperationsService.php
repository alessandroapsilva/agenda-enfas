<?php

namespace App\Services\Enfas;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConfirmationOperationsService
{
    public function snapshot(): array
    {
        $periodEnd = now()->endOfDay();

        $periodStart = $periodEnd
            ->copy()
            ->subDays(6)
            ->startOfDay();

        $appointments = DB::table(
            'appointments'
        )
            ->whereBetween(
                'start_at',
                [$periodStart, $periodEnd]
            )
            ->get([
                'id',
                'start_at',
                'status',
                'confirmation_status',
                'confirmed_at',
            ]);

        $total7d = $appointments->count();

        $confirmed7d = $appointments
            ->filter(
                fn ($row) =>
                    $row->status === 'confirmed'
                    || $row->confirmation_status === 'confirmed'
            )
            ->count();

        $noShow7d = $appointments
            ->where(
                'status',
                'no_show'
            )
            ->count();

        $cancelled7d = $appointments
            ->filter(
                fn ($row) => in_array(
                    $row->status,
                    ['cancelled', 'canceled'],
                    true
                )
            )
            ->count();

        $resolved7d = $confirmed7d
            + $noShow7d
            + $cancelled7d;

        $confirmationRate = $total7d > 0
            ? round(
                ($confirmed7d / $total7d) * 100,
                1
            )
            : 0.0;

        $resolutionRate = $total7d > 0
            ? round(
                ($resolved7d / $total7d) * 100,
                1
            )
            : 0.0;

        $leadMinutes = $appointments
            ->filter(
                fn ($row) =>
                    $row->confirmed_at
                    && (
                        $row->status === 'confirmed'
                        || $row->confirmation_status === 'confirmed'
                    )
            )
            ->map(
                function ($row) {
                    $start = Carbon::parse(
                        $row->start_at
                    );

                    $confirmed = Carbon::parse(
                        $row->confirmed_at
                    );

                    return max(
                        0,
                        $confirmed->diffInMinutes(
                            $start,
                            false
                        )
                    );
                }
            )
            ->filter(
                fn ($minutes) => $minutes >= 0
            );

        $avgLeadHours = $leadMinutes->isNotEmpty()
            ? round(
                $leadMinutes->avg() / 60,
                1
            )
            : null;

        return [
            'period' => [
                'start' => $periodStart,
                'end' => $periodEnd,
            ],

            'performance' => [
                'total_7d' => $total7d,
                'confirmed_7d' => $confirmed7d,
                'cancelled_7d' => $cancelled7d,
                'no_show_7d' => $noShow7d,
                'confirmation_rate' => $confirmationRate,
                'resolution_rate' => $resolutionRate,
                'avg_lead_hours' => $avgLeadHours,
            ],

            'risk' => $this->riskSnapshot(),

            'whatsapp' => $this->whatsAppSnapshot(
                $periodStart,
                $periodEnd
            ),

            'voice' => $this->voiceSnapshot(
                $periodStart,
                $periodEnd
            ),
        ];
    }

    private function riskSnapshot(): array
    {
        $base = DB::table(
            'appointments as a'
        )
            ->leftJoin(
                'patients as p',
                'p.id',
                '=',
                'a.patient_id'
            )
            ->where(
                'a.start_at',
                '>',
                now()
            )
            ->where(
                'a.start_at',
                '<=',
                now()->addDay()
            )
            ->whereNotIn(
                'a.status',
                [
                    'confirmed',
                    'cancelled',
                    'canceled',
                    'completed',
                    'no_show',
                ]
            )
            ->where(
                function ($query) {
                    $query
                        ->where(
                            'a.confirmation_status',
                            'pending'
                        )
                        ->orWhere(
                            'a.status',
                            'awaiting_confirmation'
                        );
                }
            );

        $next24h = (clone $base)->count();

        $next2h = (clone $base)
            ->where(
                'a.start_at',
                '<=',
                now()->addHours(2)
            )
            ->count();

        $withoutPhone = (clone $base)
            ->where(
                function ($query) {
                    $query
                        ->whereNull(
                            'p.phone'
                        )
                        ->orWhere(
                            'p.phone',
                            ''
                        );
                }
            )
            ->count();

        $contactBlocked = (clone $base)
            ->where(
                function ($query) {
                    $query
                        ->where(
                            'p.do_not_contact',
                            true
                        )
                        ->orWhere(
                            'p.contact_consent',
                            false
                        );
                }
            )
            ->count();

        $failedContact = 0;

        if (Schema::hasTable('wa_messages')) {
            $failedContact = (clone $base)
                ->whereRaw(
                    "(SELECT wm.status FROM wa_messages wm WHERE wm.appointment_id = a.id ORDER BY wm.id DESC LIMIT 1) = 'failed'"
                )
                ->count();
        }

        return [
            'next_24h' => $next24h,
            'next_2h' => $next2h,
            'without_phone' => $withoutPhone,
            'contact_blocked' => $contactBlocked,
            'failed_contact' => $failedContact,
        ];
    }

    private function whatsAppSnapshot(
        Carbon $start,
        Carbon $end
    ): array {
        $base = [
            'available' => false,
            'outbound_7d' => 0,
            'delivered_7d' => 0,
            'read_7d' => 0,
            'failed_7d' => 0,
            'failed_24h' => 0,
            'failure_rate' => 0.0,
            'active_automations' => 0,
            'last_success_at' => null,
        ];

        if (! Schema::hasTable(
            'wa_messages'
        )) {
            return $base;
        }

        $base['available'] = true;

        $outbound = DB::table(
            'wa_messages'
        )
            ->whereBetween(
                'created_at',
                [$start, $end]
            )
            ->where(
                'direction',
                'outbound'
            );

        $base['outbound_7d'] =
            (clone $outbound)->count();

        $base['delivered_7d'] =
            (clone $outbound)
                ->whereIn(
                    'status',
                    ['delivered', 'read']
                )
                ->count();

        $base['read_7d'] =
            (clone $outbound)
                ->where(
                    'status',
                    'read'
                )
                ->count();

        $base['failed_7d'] =
            (clone $outbound)
                ->where(
                    'status',
                    'failed'
                )
                ->count();

        $base['failed_24h'] =
            DB::table('wa_messages')
                ->where(
                    'direction',
                    'outbound'
                )
                ->where(
                    'created_at',
                    '>=',
                    now()->subDay()
                )
                ->where(
                    'status',
                    'failed'
                )
                ->count();

        $base['failure_rate'] =
            $base['outbound_7d'] > 0
                ? round(
                    (
                        $base['failed_7d']
                        / $base['outbound_7d']
                    ) * 100,
                    1
                )
                : 0.0;

        $base['last_success_at'] =
            DB::table('wa_messages')
                ->where(
                    'direction',
                    'outbound'
                )
                ->whereIn(
                    'status',
                    [
                        'sent',
                        'delivered',
                        'read',
                    ]
                )
                ->max('updated_at');

        if (Schema::hasTable(
            'wa_automations'
        )) {
            $base['active_automations'] =
                DB::table(
                    'wa_automations'
                )
                    ->where(
                        'is_active',
                        true
                    )
                    ->count();
        }

        return $base;
    }

    private function voiceSnapshot(
        Carbon $start,
        Carbon $end
    ): array {
        $enabled = (bool) config(
            'services.voice.enabled',
            false
        );

        $provider = (string) config(
            'services.voice.provider',
            'twilio'
        );

        $webhookValidation = (bool) config(
            'services.voice.twilio.validate_webhooks',
            true
        );

        $configured =
            filled(
                config(
                    'services.voice.twilio.account_sid'
                )
            )
            && filled(
                config(
                    'services.voice.twilio.auth_token'
                )
            )
            && filled(
                config(
                    'services.voice.twilio.from'
                )
            );

        $base = [
            'available' => Schema::hasTable(
                'confirmation_attempts'
            ),
            'provider' => $provider,
            'enabled' => $enabled,
            'configured' => $configured,
            'webhook_validation' =>
                $webhookValidation,
            'queued' => 0,
            'in_progress' => 0,
            'attempts_7d' => 0,
            'completed_7d' => 0,
            'confirmed_7d' => 0,
            'no_answer_7d' => 0,
            'provider_failed_7d' => 0,
            'oldest_queue_at' => null,
        ];

        if (! $base['available']) {
            return $base;
        }

        $base['queued'] =
            DB::table(
                'confirmation_attempts'
            )
                ->where(
                    'channel',
                    'voice'
                )
                ->where(
                    'status',
                    'queued'
                )
                ->count();

        $base['in_progress'] =
            DB::table(
                'confirmation_attempts'
            )
                ->where(
                    'channel',
                    'voice'
                )
                ->where(
                    'status',
                    'in_progress'
                )
                ->count();

        $period = DB::table(
            'confirmation_attempts'
        )
            ->where(
                'channel',
                'voice'
            )
            ->whereBetween(
                'created_at',
                [$start, $end]
            );

        $base['attempts_7d'] =
            (clone $period)->count();

        $base['completed_7d'] =
            (clone $period)
                ->where(
                    'status',
                    'completed'
                )
                ->count();

        $base['confirmed_7d'] =
            (clone $period)
                ->where(
                    'outcome',
                    'confirmed'
                )
                ->count();

        $base['no_answer_7d'] =
            (clone $period)
                ->where(
                    'outcome',
                    'no_answer'
                )
                ->count();

        $base['provider_failed_7d'] =
            (clone $period)
                ->where(
                    'outcome',
                    'provider_failed'
                )
                ->count();

        $base['oldest_queue_at'] =
            DB::table(
                'confirmation_attempts'
            )
                ->where(
                    'channel',
                    'voice'
                )
                ->where(
                    'status',
                    'queued'
                )
                ->min(
                    'scheduled_at'
                );

        return $base;
    }
}
