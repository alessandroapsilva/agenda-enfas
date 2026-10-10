<?php

namespace App\Console\Commands;

use App\Jobs\SendAppointmentWhatsApp;
use App\Models\WaAutomation;
use App\Models\WaMessage;
use App\Services\Enfas\WhatsAppDispatchPolicy;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProcessEnfasReminders extends Command
{
    protected $signature =
        'enfas:reminders {--dry-run : Mostra o que seria processado sem enviar}';

    protected $description =
        'Processa apenas automacoes temporais e lembretes do ENFAS Agenda';

    public function handle(
        WhatsAppDispatchPolicy $policy
    ): int {
        if (! Schema::hasTable('appointments')
            || ! Schema::hasTable('wa_automations')) {
            $this->warn(
                'Estrutura de automacoes nao disponivel.'
            );

            return self::SUCCESS;
        }

        $rules = WaAutomation::with('template')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($rules->isEmpty()) {
            $this->warn(
                'Nenhuma automacao ativa.'
            );

            return self::SUCCESS;
        }

        $now = now();

        $stats = [
            'rules' => 0,
            'scheduled' => 0,
            'event_driven' => 0,
            'due' => 0,
            'queued' => 0,
            'deduped' => 0,
            'cooldown' => 0,
            'blocked' => 0,
            'unsupported' => 0,
        ];

        foreach ($rules as $rule) {
            $stats['rules']++;

            if (! $policy->isScheduledEvent(
                (string) $rule->trigger_event
            )) {
                $stats['event_driven']++;
                continue;
            }

            $stats['scheduled']++;

            $template = $rule->template;

            if (! $template
                || $template->status !== 'APPROVED'
                || ! (bool) ($template->is_active ?? true)
                || $template->archived_at !== null) {
                $this->warn(
                    "Regra #{$rule->id} ignorada: template indisponivel."
                );

                continue;
            }

            $appointments = $this->appointmentsFor(
                $rule,
                $now
            );

            if ($appointments === null) {
                $stats['unsupported']++;

                $this->warn(
                    "Regra #{$rule->id} ({$rule->trigger_event}) sem evento temporal confiavel."
                );

                continue;
            }

            $stats['due'] +=
                $appointments->count();

            foreach ($appointments as $appointment) {
                $purpose = (string) (
                    $template->purpose
                    ?: 'general'
                );

                if (! $policy->automatedMessageAllowed(
                    (int) $appointment->id,
                    $purpose
                )) {
                    $stats['blocked']++;
                    continue;
                }

                $offset = (int) (
                    $rule->offset_minutes
                    ?? 0
                );

                if ($policy->hasRecentAutomatedMessage(
                    (int) $appointment->id,
                    10
                )) {
                    $stats['cooldown']++;
                    continue;
                }

                $dedupe = $policy
                    ->canonicalDedupeKey(
                        $appointment,
                        $purpose,
                        (string) $rule->trigger_event,
                        $offset
                    );

                $exact = WaMessage::query()
                    ->where(
                        'dedupe_key',
                        $dedupe
                    )
                    ->first();

                if ($policy->blocksRetry(
                    $exact,
                    (int) $rule->template_id
                )) {
                    $stats['deduped']++;
                    continue;
                }

                $equivalent = $policy
                    ->existingEquivalentMessage(
                        $appointment,
                        $purpose,
                        (string) $rule->trigger_event,
                        $offset
                    );

                if ($equivalent) {
                    $stats['deduped']++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line(
                        "DRY-RUN regra={$rule->id} evento={$rule->trigger_event} agendamento={$appointment->id} dedupe={$dedupe}"
                    );

                    continue;
                }

                SendAppointmentWhatsApp::dispatch(
                    (int) $appointment->id,
                    (int) $rule->template_id,
                    (int) $rule->id,
                    $dedupe
                );

                $stats['queued']++;
            }

            if (! $this->option('dry-run')) {
                $this->touchRule(
                    $rule,
                    $now
                );
            }
        }

        $this->newLine();

        $this->table(
            [
                'Regras',
                'Temporais',
                'Por evento',
                'Elegiveis',
                'Enfileiradas',
                'Deduplicadas',
                'Cooldown',
                'Bloqueadas',
                'Sem suporte',
            ],
            [[
                $stats['rules'],
                $stats['scheduled'],
                $stats['event_driven'],
                $stats['due'],
                $stats['queued'],
                $stats['deduped'],
                $stats['cooldown'],
                $stats['blocked'],
                $stats['unsupported'],
            ]]
        );

        return self::SUCCESS;
    }

    private function appointmentsFor(
        WaAutomation $rule,
        Carbon $now
    ): ?\Illuminate\Support\Collection {
        $base = DB::table('appointments');

        if ($rule->service_id) {
            $base->where(
                'service_id',
                $rule->service_id
            );
        }

        return match ($rule->trigger_event) {
            'appointment_before' =>
                $this->beforeAppointments(
                    clone $base,
                    (int) ($rule->offset_minutes ?? 0),
                    $now
                ),

            'appointment_return_due' =>
                $this->returnDueAppointments(
                    clone $base,
                    $now
                ),

            default => null,
        };
    }

    private function beforeAppointments(
        Builder $query,
        int $offsetMinutes,
        Carbon $now
    ): \Illuminate\Support\Collection {
        if (! Schema::hasColumn(
            'appointments',
            'start_at'
        )) {
            return collect();
        }

        $offset = max(
            0,
            $offsetMinutes
        );

        $target = $now->copy()->addMinutes(
            $offset
        );

        $windowStart = $target->copy()
            ->subMinutes(5);

        $windowEnd = $target->copy()
            ->addMinute();

        return $query
            ->whereNotIn(
                'status',
                [
                    'cancelled',
                    'canceled',
                    'completed',
                    'no_show',
                ]
            )
            ->where(
                'start_at',
                '>',
                $now
            )
            ->whereBetween(
                'start_at',
                [
                    $windowStart,
                    $windowEnd,
                ]
            )
            ->get();
    }

    private function returnDueAppointments(
        Builder $query,
        Carbon $now
    ): \Illuminate\Support\Collection {
        if (! Schema::hasColumn(
            'appointments',
            'return_due_at'
        )) {
            return collect();
        }

        return $query
            ->where(
                'status',
                'completed'
            )
            ->whereNotNull(
                'return_due_at'
            )
            ->whereDate(
                'return_due_at',
                '=',
                $now->toDateString()
            )
            ->get();
    }

    private function touchRule(
        WaAutomation $rule,
        Carbon $time
    ): void {
        $rule->forceFill([
            'last_run_at' => $time,
        ])->save();
    }
}
