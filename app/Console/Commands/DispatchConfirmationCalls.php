<?php

namespace App\Console\Commands;

use App\Services\Enfas\ConfirmationPolicyService;
use App\Services\Enfas\Voice\VoiceProviderManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DispatchConfirmationCalls extends Command
{
    protected $signature = 'enfas:dispatch-confirmation-calls
        {--dry-run : Mostra o que seria discado sem iniciar chamadas}';

    protected $description =
        'Despacha chamadas de confirmacao pendentes para o provedor de voz';

    public function handle(
        VoiceProviderManager $manager,
        ConfirmationPolicyService $policy
    ): int {
        if (! Schema::hasTable(
            'confirmation_attempts'
        )) {
            $this->warn(
                'Estrutura de confirmacao multicanal indisponivel.'
            );

            return self::SUCCESS;
        }

        $provider = $manager->driver();

        if (! $this->option('dry-run')) {
            $this->cancelResolvedAttempts();
        }

        if (! $this->option('dry-run')
            && ! $provider->enabled()) {
            $this->info(
                'Chamadas externas desativadas por configuracao.'
            );

            return self::SUCCESS;
        }

        if (! $this->option('dry-run')
            && ! $provider->ready()) {
            $this->warn(
                'Provedor de voz habilitado, mas incompleto.'
            );

            return self::FAILURE;
        }

        $attempts = DB::table(
            'confirmation_attempts as ca'
        )
            ->join(
                'appointments as a',
                'a.id',
                '=',
                'ca.appointment_id'
            )
            ->leftJoin(
                'patients as p',
                'p.id',
                '=',
                'a.patient_id'
            )
            ->where(
                'ca.channel',
                'voice'
            )
            ->where(
                'ca.status',
                'queued'
            )
            ->where(function ($q) {
                $q->whereNull(
                    'ca.scheduled_at'
                )
                    ->orWhere(
                        'ca.scheduled_at',
                        '<=',
                        now()
                    );
            })
            ->where(
                'a.start_at',
                '>',
                now()
            )
            ->where(function ($q) {
                $q->where(
                    'a.confirmation_status',
                    'pending'
                )
                    ->orWhere(
                        'a.status',
                        'awaiting_confirmation'
                    );
            })
            ->orderBy(
                'ca.scheduled_at'
            )
            ->orderBy(
                'ca.id'
            )
            ->limit(10)
            ->get([
                'ca.*',
                'a.patient_id',
                'a.professional_id',
                'a.service_id',
                'a.start_at as appointment_start_at',
                'p.phone as patient_phone',
                'p.contact_consent',
                'p.do_not_contact',
            ]);

        $stats = [
            'due' => $attempts->count(),
            'dispatched' => 0,
            'failed' => 0,
        ];

        foreach ($attempts as $attempt) {
            if (! $policy->voiceFallbackEnabled()
                || ! $policy->patientContactAllowed(
                    $attempt
                )
                || ! $policy->matchesScope(
                    $attempt
                )) {
                if ($this->option('dry-run')) {
                    $this->line(
                        'DRY-RUN bloqueada por politica tentativa='
                        .$attempt->id
                    );

                    continue;
                }

                $this->completeWithoutCall(
                    (int) $attempt->id,
                    'blocked_by_policy',
                    'Tentativa bloqueada pela política de confirmação.'
                );

                continue;
            }

            if (! $policy->hasVoiceNumber(
                $attempt
            )) {
                if ($this->option('dry-run')) {
                    $this->line(
                        'DRY-RUN sem telefone tentativa='
                        .$attempt->id
                    );

                    continue;
                }

                $this->completeWithoutCall(
                    (int) $attempt->id,
                    'invalid_number',
                    'Paciente sem telefone válido para ligação automática.'
                );

                $this->createHumanFallbackTask(
                    $attempt,
                    $policy
                );

                continue;
            }

            if (! $policy->isWithinCallWindow()) {
                $nextAllowed =
                    $policy->nextAllowedCallAt();

                $appointmentStart =
                    \Illuminate\Support\Carbon::parse(
                        $attempt->appointment_start_at
                    );

                if ($nextAllowed->gte(
                    $appointmentStart
                )) {
                    if ($this->option('dry-run')) {
                        $this->line(
                            'DRY-RUN janela expirada tentativa='
                            .$attempt->id
                        );

                        continue;
                    }

                    $this->completeWithoutCall(
                        (int) $attempt->id,
                        'window_expired',
                        'Próxima janela permitida ocorre após o horário do agendamento.'
                    );

                    $this->createHumanFallbackTask(
                        $attempt,
                        $policy
                    );

                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line(
                        'DRY-RUN reagendada tentativa='
                        .$attempt->id
                        .' para '
                        .$nextAllowed->format('d/m H:i')
                    );

                    continue;
                }

                DB::table(
                    'confirmation_attempts'
                )
                    ->where(
                        'id',
                        $attempt->id
                    )
                    ->update([
                        'scheduled_at' =>
                            $nextAllowed,
                        'updated_at' =>
                            now(),
                    ]);

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line(
                    'DRY-RUN tentativa='
                    .$attempt->id
                    .' agenda='
                    .$attempt->appointment_id
                    .' destino='
                    .($attempt->recipient ?: '-')
                );

                continue;
            }

            $claimed = DB::table(
                'confirmation_attempts'
            )
                ->where(
                    'id',
                    $attempt->id
                )
                ->where(
                    'status',
                    'queued'
                )
                ->update([
                    'status' => 'in_progress',
                    'provider' => $provider->name(),
                    'started_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($claimed !== 1) {
                continue;
            }

            try {
                $result =
                    $provider->placeConfirmationCall(
                        $attempt
                    );

                DB::table(
                    'confirmation_attempts'
                )
                    ->where(
                        'id',
                        $attempt->id
                    )
                    ->update([
                        'provider' =>
                            $provider->name(),
                        'external_id' =>
                            $result['external_id'],
                        'metadata' =>
                            $this->mergeMetadata(
                                $attempt->metadata,
                                [
                                    'provider_status' =>
                                        $result['status'],
                                    'dispatched_at' =>
                                        now()->toIso8601String(),
                                ]
                            ),
                        'updated_at' =>
                            now(),
                    ]);

                $stats['dispatched']++;
            } catch (Throwable $e) {
                report($e);

                DB::table(
                    'confirmation_attempts'
                )
                    ->where(
                        'id',
                        $attempt->id
                    )
                    ->update([
                        'status' =>
                            'completed',
                        'outcome' =>
                            'provider_failed',
                        'completed_at' =>
                            now(),
                        'notes' =>
                            mb_substr(
                                $e->getMessage(),
                                0,
                                1000
                            ),
                        'updated_at' =>
                            now(),
                    ]);

                $stats['failed']++;

                $this->error(
                    'Tentativa #'
                    .$attempt->id
                    .': '
                    .$e->getMessage()
                );
            }
        }

        $this->newLine();

        $this->table(
            [
                'Pendentes',
                'Despachadas',
                'Falharam',
            ],
            [[
                $stats['due'],
                $stats['dispatched'],
                $stats['failed'],
            ]]
        );

        return self::SUCCESS;
    }

    private function completeWithoutCall(
        int $attemptId,
        string $outcome,
        string $notes
    ): void {
        DB::table(
            'confirmation_attempts'
        )
            ->where(
                'id',
                $attemptId
            )
            ->update([
                'status' =>
                    'completed',
                'outcome' =>
                    $outcome,
                'completed_at' =>
                    now(),
                'notes' =>
                    $notes,
                'updated_at' =>
                    now(),
            ]);
    }

    private function createHumanFallbackTask(
        object $attempt,
        ConfirmationPolicyService $policy
    ): void {
        if (! $policy->humanFallbackEnabled()
            || ! Schema::hasTable('clinic_tasks')) {
            return;
        }

        $exists = DB::table('clinic_tasks')
            ->where(
                'appointment_id',
                $attempt->appointment_id
            )
            ->where(
                'status',
                'open'
            )
            ->where(
                'title',
                'Confirmar agendamento por contato humano'
            )
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('clinic_tasks')->insert([
            'patient_id' =>
                $attempt->patient_id ?? null,
            'appointment_id' =>
                $attempt->appointment_id,
            'conversation_id' =>
                null,
            'title' =>
                'Confirmar agendamento por contato humano',
            'notes' =>
                'Fallback humano criado pela régua multicanal de confirmação.',
            'priority' =>
                'high',
            'status' =>
                'open',
            'due_at' =>
                now(),
            'assigned_user_id' =>
                null,
            'created_by' =>
                null,
            'completed_at' =>
                null,
            'created_at' =>
                now(),
            'updated_at' =>
                now(),
        ]);
    }

    private function cancelResolvedAttempts(): void
    {
        $resolvedIds = DB::table(
            'confirmation_attempts as ca'
        )
            ->join(
                'appointments as a',
                'a.id',
                '=',
                'ca.appointment_id'
            )
            ->where(
                'ca.channel',
                'voice'
            )
            ->whereIn(
                'ca.status',
                ['queued', 'in_progress']
            )
            ->where(function ($q) {
                $q
                    ->where(
                        'a.confirmation_status',
                        'confirmed'
                    )
                    ->orWhereIn(
                        'a.status',
                        [
                            'confirmed',
                            'cancelled',
                            'canceled',
                            'completed',
                        ]
                    );
            })
            ->pluck('ca.id');

        if ($resolvedIds->isEmpty()) {
            return;
        }

        DB::table(
            'confirmation_attempts'
        )
            ->whereIn(
                'id',
                $resolvedIds->all()
            )
            ->update([
                'status' =>
                    'completed',
                'outcome' =>
                    'resolved_elsewhere',
                'completed_at' =>
                    now(),
                'updated_at' =>
                    now(),
            ]);
    }

    private function mergeMetadata(
        mixed $raw,
        array $extra
    ): string {
        $current = [];

        if (is_string($raw)
            && $raw !== '') {
            $decoded =
                json_decode(
                    $raw,
                    true
                );

            if (is_array($decoded)) {
                $current = $decoded;
            }
        }

        return json_encode(
            array_merge(
                $current,
                $extra
            ),
            JSON_UNESCAPED_UNICODE
        );
    }
}
