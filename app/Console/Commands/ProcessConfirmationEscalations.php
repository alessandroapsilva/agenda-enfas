<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProcessConfirmationEscalations extends Command
{
    protected $signature = 'enfas:confirmation-escalations {--dry-run : Mostra a fila sem criar tentativas}';

    protected $description = 'Cria a fila de escalonamento de confirmacoes para ligacao';

    public function handle(): int
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
                'a.code',
                'a.start_at',
                'a.status',
                'a.confirmation_status',
                'p.phone as patient_phone',
            ]);

        $stats = [
            'eligible' => 0,
            'queued' => 0,
            'deduped' => 0,
            'skipped' => 0,
        ];

        foreach ($rows as $row) {
            $reason = $this->reasonFor(
                (int) $row->id,
                Carbon::parse($row->start_at),
                $now
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
                        'invalid_number',
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
                    ->greaterThan($now->copy()->subMinutes(30))) {
                $stats['deduped']++;
                continue;
            }

            $attemptNo = $lastAttempt
                ? ((int) $lastAttempt->attempt_no + 1)
                : 1;

            if ($attemptNo > 3) {
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

            if ($this->option('dry-run')) {
                $this->line(
                    "DRY-RUN agendamento={$row->id} tentativa={$attemptNo} motivo={$reason}"
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
                'scheduled_at' => $now,
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
        Carbon $now
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
            && $minutesUntil <= 120) {
            return 'no_response_2h';
        }

        if (! $latestOutbound
            && $minutesUntil >= 0
            && $minutesUntil <= 360) {
            return 'no_whatsapp_6h';
        }

        return null;
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
