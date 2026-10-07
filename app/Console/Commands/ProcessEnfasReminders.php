<?php

namespace App\Console\Commands;

use App\Jobs\SendAppointmentWhatsApp;
use App\Models\WaAutomation;
use App\Models\WaMessage;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProcessEnfasReminders extends Command
{
    protected $signature = 'enfas:reminders {--dry-run : Mostra o que seria processado sem enviar}';
    protected $description = 'Processa automações e lembretes do ENFAS Agenda';

    public function handle(): int
    {
        if (! Schema::hasTable('appointments')
            || ! Schema::hasTable('wa_automations')) {
            $this->warn('Estrutura de automações não disponível.');
            return self::SUCCESS;
        }

        $rules = WaAutomation::with('template')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($rules->isEmpty()) {
            $this->warn('Nenhuma automação ativa.');
            return self::SUCCESS;
        }

        $now = now();
        $stats = [
            'rules' => 0,
            'due' => 0,
            'queued' => 0,
            'deduped' => 0,
            'unsupported' => 0,
        ];

        foreach ($rules as $rule) {
            $stats['rules']++;

            if (! $rule->template
                || $rule->template->status !== 'APPROVED') {
                $this->warn(
                    "Regra #{$rule->id} ignorada: template não aprovado."
                );
                continue;
            }

            if (isset($rule->template->is_active)
                && ! (bool) $rule->template->is_active) {
                $this->warn(
                    "Regra #{$rule->id} ignorada: template inativo."
                );
                continue;
            }

            $appointments = $this->appointmentsFor($rule, $now);

            if ($appointments === null) {
                $stats['unsupported']++;
                $this->warn(
                    "Regra #{$rule->id} ({$rule->trigger_event}) sem evento confiável nesta base."
                );
                $this->touchRule($rule, $now);
                continue;
            }

            $stats['due'] += $appointments->count();

            foreach ($appointments as $appointment) {
                $dedupe = $this->dedupeKey(
                    (int) $rule->id,
                    (int) $appointment->id,
                    (string) $rule->trigger_event,
                    (int) ($rule->offset_minutes ?? 0),
                    (string) ($rule->template->purpose ?? '')
                );

                if ($rule->send_once
                    && WaMessage::where('dedupe_key', $dedupe)->exists()) {
                    $stats['deduped']++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line(
                        "DRY-RUN regra={$rule->id} evento={$rule->trigger_event} agendamento={$appointment->id}"
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

                if ($rule->trigger_event === 'appointment_created') {
                    $this->markConfirmationRequested(
                        (int) $appointment->id
                    );
                }
            }

            if (! $this->option('dry-run')) {
                $this->touchRule($rule, $now);
            }
        }

        $this->newLine();
        $this->table(
            ['Regras', 'Elegíveis', 'Enfileiradas', 'Duplicadas', 'Sem suporte'],
            [[
                $stats['rules'],
                $stats['due'],
                $stats['queued'],
                $stats['deduped'],
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

        $since = $rule->last_run_at
            ? Carbon::parse($rule->last_run_at)
            : $now->copy()->subMinutes(5);

        return match ($rule->trigger_event) {
            'appointment_created' => $this->createdAppointments(
                clone $base,
                $since,
                $now
            ),

            'appointment_before' => $this->beforeAppointments(
                clone $base,
                (int) ($rule->offset_minutes ?? 0),
                $now
            ),

            'appointment_confirmed' => $this->timestampAppointments(
                clone $base,
                'confirmed_at',
                $since,
                $now
            ),

            'appointment_cancelled' => $this->timestampAppointments(
                clone $base,
                'cancelled_at',
                $since,
                $now
            ),

            'appointment_completed' => $this->completedAppointments(
                clone $base,
                $since,
                $now
            ),

            'appointment_rescheduled' => $this->timestampAppointments(
                clone $base,
                'rescheduled_at',
                $since,
                $now
            ),

            'appointment_return_due' => $this->returnDueAppointments(
                clone $base,
                $since,
                $now
            ),

            default => null,
        };
    }

    private function createdAppointments(
        Builder $query,
        Carbon $since,
        Carbon $now
    ): \Illuminate\Support\Collection {
        if (! Schema::hasColumn('appointments', 'created_at')
            || ! Schema::hasColumn('appointments', 'start_at')) {
            return collect();
        }

        return $query
            ->whereNotIn('status', [
                'cancelled',
                'completed',
                'canceled',
            ])
            ->where('start_at', '>', $now)
            ->where('created_at', '>', $since)
            ->where('created_at', '<=', $now)
            ->get();
    }

    private function beforeAppointments(
        Builder $query,
        int $offsetMinutes,
        Carbon $now
    ): \Illuminate\Support\Collection {
        if (! Schema::hasColumn('appointments', 'start_at')) {
            return collect();
        }

        $limit = $now->copy()->addMinutes(
            max(0, $offsetMinutes)
        );

        return $query
            ->whereNotIn('status', [
                'cancelled',
                'completed',
                'canceled',
            ])
            ->where('start_at', '>', $now)
            ->where('start_at', '<=', $limit)
            ->get();
    }

    private function timestampAppointments(
        Builder $query,
        string $column,
        Carbon $since,
        Carbon $now
    ): \Illuminate\Support\Collection {
        if (! Schema::hasColumn('appointments', $column)) {
            return collect();
        }

        return $query
            ->whereNotNull($column)
            ->where($column, '>', $since)
            ->where($column, '<=', $now)
            ->get();
    }

    private function completedAppointments(
        Builder $query,
        Carbon $since,
        Carbon $now
    ): \Illuminate\Support\Collection {
        if (Schema::hasColumn('appointments', 'completed_at')) {
            return $this->timestampAppointments(
                $query,
                'completed_at',
                $since,
                $now
            );
        }

        if (Schema::hasColumn('appointments', 'attended_at')) {
            return $this->timestampAppointments(
                $query,
                'attended_at',
                $since,
                $now
            );
        }

        return collect();
    }

    private function returnDueAppointments(
        Builder $query,
        Carbon $since,
        Carbon $now
    ): \Illuminate\Support\Collection {
        if (! Schema::hasColumn('appointments', 'return_due_at')) {
            return collect();
        }

        return $query
            ->where('status', 'completed')
            ->whereNotNull('return_due_at')
            ->whereDate('return_due_at', '>=', $since->toDateString())
            ->whereDate('return_due_at', '<=', $now->toDateString())
            ->get();
    }

    private function dedupeKey(
        int $ruleId,
        int $appointmentId,
        string $event,
        int $offset,
        string $purpose
    ): string {
        if ($purpose === 'confirmation') {
            return 'auto:confirmation:event:'
                .$event
                .':appointment:'
                .$appointmentId;
        }

        return implode(':', [
            'auto',
            $ruleId,
            $event,
            $offset,
            'appointment',
            $appointmentId,
        ]);
    }

    private function touchRule(
        WaAutomation $rule,
        Carbon $time
    ): void {
        $rule->forceFill([
            'last_run_at' => $time,
        ])->save();
    }

    private function markConfirmationRequested(
        int $appointmentId
    ): void {
        $payload = [];

        if (Schema::hasColumn(
            'appointments',
            'confirmation_requested_at'
        )) {
            $payload['confirmation_requested_at'] = now();
        }

        if (Schema::hasColumn(
            'appointments',
            'confirmation_channel'
        )) {
            $payload['confirmation_channel'] = 'whatsapp';
        }

        if ($payload !== []) {
            DB::table('appointments')
                ->where('id', $appointmentId)
                ->update($payload);
        }
    }
}
