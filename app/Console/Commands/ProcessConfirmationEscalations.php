<?php

namespace App\Console\Commands;

use App\Services\Enfas\ConfirmationPolicyService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProcessConfirmationEscalations extends Command
{
    protected $signature = 'enfas:confirmation-escalations {--dry-run : Mostra a fila sem criar tentativas}';

    protected $description = 'Cria a fila de escalonamento de confirmacoes para ligacao';

    public function handle(
        ConfirmationPolicyService $policy
    ): int
    {
        if (! Schema::hasTable('appointments')
            || ! Schema::hasTable('confirmation_attempts')) {
            $this->warn('Estrutura de confirmacao multicanal indisponivel.');
            return self::SUCCESS;
        }

        $now = now();
        $until = $now->copy()->addDay();

        $rows = DB::table('appointments as a')
            ->leftJoin(
                'patients as p',
                'p.id',
                '=',
                'a.patient_id'
            )
            ->where('a.start_at', '>', $now)
            ->where('a.start_at', '<=', $until)
            ->where(function ($q) {
                $q->where('a.confirmation_status', 'pending')
                    ->orWhere('a.status', 'awaiting_confirmation');
            })
            ->whereNotIn('a.status', [
                'cancelled',
                'canceled',
                'completed',
            ])
            ->orderBy('a.start_at')
            ->get([
                'a.id',
                'a.patient_id',
                'a.code',
                'a.start_at',
                'a.status',
                'a.confirmation_status',
                'a.professional_id',
                'a.service_id',
                'p.phone as patient_phone',
                'p.contact_consent',
                'p.do_not_contact',
            ]);

        $stats = [
            'eligible' => 0,
            'queued' => 0,
            'deduped' => 0,
            'skipped' => 0,
        ];

        foreach ($rows as $row) {
            if (! $policy->voiceFallbackEnabled()
                || ! $policy->patientContactAllowed(
                    $row
                )
                || ! $policy->matchesScope(
                    $row
                )) {
                $stats['skipped']++;
                continue;
            }

            if (! $policy->hasVoiceNumber(
                $row
            )) {
                $this->createHumanFallbackTask(
                    $row,
                    $policy
                );

                $stats['skipped']++;
                continue;
            }

            $reason = $this->reasonFor(
                (int) $row->id,
                Carbon::parse($row->start_at),
                $now,
                $policy
            );

            if (! $reason) {
                $stats['skipped']++;
                continue;
            }

            $stats['eligible']++;

            $lastAttempt = DB::table('confirmation_attempts')
                ->where('appointment_id', $row->id)
                ->where('channel', 'voice')
                ->orderByDesc('id')
                ->first();

            if ($lastAttempt
                && in_array(
                    $lastAttempt->status,
                    ['queued', 'in_progress'],
                    true
                )) {
                $stats['deduped']++;
                continue;
            }

            if ($lastAttempt
                && in_array(
                    $lastAttempt->outcome,
                    [
                        'confirmed',
                        'cancelled',
                        'callback',
                        'resolved_elsewhere',
                    ],
                    true
                )) {
                $stats['deduped']++;
                continue;
            }

            if ($lastAttempt
                && $lastAttempt->completed_at
                && Carbon::parse($lastAttempt->completed_at)
                    ->greaterThan(
                        $now->copy()->subMinutes(
                            $policy->retryMinutes()
                        )
                    )) {
                $stats['deduped']++;
                continue;
            }

            $attemptNo = $lastAttempt
                ? ((int) $lastAttempt->attempt_no + 1)
                : 1;

            if (
                $attemptNo
                > $policy->maxVoiceAttempts()
            ) {
                $this->createHumanFallbackTask(
                    $row,
                    $policy
                );

                $stats['deduped']++;
                continue;
            }

            $dedupe = implode(':', [
                'confirmation',
                'voice',
                'appointment',
                $row->id,
                'attempt',
                $attemptNo,
            ]);

            if (DB::table('confirmation_attempts')
                ->where('dedupe_key', $dedupe)
                ->exists()) {
                $stats['deduped']++;
                continue;
            }

            $scheduledAt =
                $policy->nextAllowedCallAt(
                    $now
                );

            if (
                $scheduledAt->gte(
                    Carbon::parse(
                        $row->start_at
                    )
                )
            ) {
                $this->createHumanFallbackTask(
                    $row,
                    $policy
                );

                $stats['skipped']++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line(
                    "DRY-RUN agendamento={$row->id} tentativa={$attemptNo} motivo={$reason} previsto={$scheduledAt->format('d/m H:i')}"
                );
                continue;
            }

            DB::table('confirmation_attempts')->insert([
                'appointment_id' => $row->id,
                'channel' => 'voice',
                'status' => 'queued',
                'outcome' => null,
                'reason' => $reason,
                'source' => 'automation',
                'provider' => null,
                'recipient' => $this->normalizePhone(
                    $row->patient_phone
                ),
                'attempt_no' => $attemptNo,
                'dedupe_key' => $dedupe,
                'scheduled_at' => $scheduledAt,
                'metadata' => json_encode([
                    'appointment_code' => $row->code,
                    'escalated_at' => $now->toIso8601String(),
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $stats['queued']++;
        }

        $this->newLine();
        $this->table(
            ['Elegiveis', 'Na fila', 'Deduplicadas', 'Ignoradas'],
            [[
                $stats['eligible'],
                $stats['queued'],
                $stats['deduped'],
                $stats['skipped'],
            ]]
        );

        return self::SUCCESS;
    }

    private function reasonFor(
        int $appointmentId,
        Carbon $startAt,
        Carbon $now,
        ConfirmationPolicyService $policy
    ): ?string {
        $latestOutbound = Schema::hasTable('wa_messages')
            ? DB::table('wa_messages')
                ->where('appointment_id', $appointmentId)
                ->where('direction', 'outbound')
                ->orderByDesc('id')
                ->first()
            : null;

        $minutesUntil = $now->diffInMinutes(
            $startAt,
            false
        );

        if ($latestOutbound
            && $latestOutbound->status === 'failed') {
            return 'whatsapp_failed';
        }

        if ($minutesUntil >= 0
            && $minutesUntil
                <= $policy->voiceEscalationMinutes()) {
            return 'no_response_voice_window';
        }

        if (! $latestOutbound
            && $minutesUntil >= 0
            && $minutesUntil
                <= $policy->noWhatsappMinutes()) {
            return 'no_whatsapp_voice_window';
        }

        return null;
    }

    private function createHumanFallbackTask(
        object $row,
        ConfirmationPolicyService $policy
    ): void {
        if (! $policy->humanFallbackEnabled()
            || ! Schema::hasTable('clinic_tasks')) {
            return;
        }

        $exists = DB::table('clinic_tasks')
            ->where(
                'appointment_id',
                $row->id
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

        if ($this->option('dry-run')) {
            $this->line(
                "DRY-RUN fallback humano agenda={$row->id}"
            );
            return;
        }

        DB::table('clinic_tasks')->insert([
            'patient_id' =>
                $row->patient_id ?? null,
            'appointment_id' =>
                $row->id,
            'conversation_id' =>
                null,
            'title' =>
                'Confirmar agendamento por contato humano',
            'notes' =>
                'Escalonamento automático da Central de Confirmações após indisponibilidade ou esgotamento da régua de voz.',
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

    private function normalizePhone(?string $raw): ?string
    {
        $digits = preg_replace(
            '/\D+/',
            '',
            (string) $raw
        );

        return $digits !== '' ? $digits : null;
    }
}
