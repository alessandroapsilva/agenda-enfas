<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConfirmationHealthCheck extends Command
{
    protected $signature =
        'enfas:confirmation-health';

    protected $description =
        'Valida a saude operacional da central de confirmacoes';

    public function handle(): int
    {
        $rows = [];
        $critical = 0;

        $this->check(
            $rows,
            'Banco',
            'confirmation_attempts',
            Schema::hasTable(
                'confirmation_attempts'
            ),
            'Tabela multicanal disponivel',
            'Tabela multicanal ausente',
            $critical
        );

        $this->checkReminderRules(
            $rows,
            $critical
        );

        $this->checkVoiceRoutes(
            $rows,
            $critical
        );

        $this->checkVoiceConfiguration(
            $rows,
            $critical
        );

        $this->appendQueueSnapshot(
            $rows
        );

        $this->appendWhatsAppSnapshot(
            $rows
        );

        $this->table(
            [
                'Area',
                'Item',
                'Estado',
                'Detalhe',
            ],
            $rows
        );

        if ($critical > 0) {
            $this->newLine();

            $this->error(
                "Health-check encontrou {$critical} falha(s) critica(s)."
            );

            return self::FAILURE;
        }

        $this->newLine();

        $this->info(
            'Central de confirmacoes operacional.'
        );

        return self::SUCCESS;
    }

    private function checkReminderRules(
        array &$rows,
        int &$critical
    ): void {
        if (! Schema::hasTable(
            'wa_automations'
        )
            || ! Schema::hasTable(
                'wa_templates'
            )) {
            $rows[] = [
                'WhatsApp',
                'Lembretes 24h/2h',
                'ERRO',
                'Estrutura de automacao indisponivel',
            ];

            $critical++;

            return;
        }

        $rules = DB::table(
            'wa_automations as a'
        )
            ->leftJoin(
                'wa_templates as t',
                't.id',
                '=',
                'a.template_id'
            )
            ->whereIn(
                'a.id',
                [2, 3]
            )
            ->orderBy(
                'a.id'
            )
            ->get([
                'a.id',
                'a.name',
                'a.is_active',
                'a.offset_minutes',
                't.id as template_id',
                't.name as template_name',
                't.status as template_status',
                't.is_active as template_active',
                't.archived_at',
            ]);

        if ($rules->count() !== 2) {
            $rows[] = [
                'WhatsApp',
                'Lembretes 24h/2h',
                'ERRO',
                'Automacoes #2/#3 incompletas',
            ];

            $critical++;

            return;
        }

        foreach ($rules as $rule) {
            $valid =
                (bool) $rule->is_active
                && $rule->template_id
                && $rule->template_status === 'APPROVED'
                && (bool) $rule->template_active
                && $rule->archived_at === null;

            $this->check(
                $rows,
                'WhatsApp',
                $rule->name
                    ?: 'Automacao #'.$rule->id,
                $valid,
                sprintf(
                    '%smin · template #%s %s',
                    $rule->offset_minutes,
                    $rule->template_id ?: '-',
                    $rule->template_name ?: '-'
                ),
                'Regra ou template indisponivel',
                $critical
            );
        }
    }

    private function checkVoiceRoutes(
        array &$rows,
        int &$critical
    ): void {
        $routes = app('router')
            ->getRoutes();

        foreach ([
            'voice.twilio.answer',
            'voice.twilio.gather',
            'voice.twilio.status',
        ] as $name) {
            $this->check(
                $rows,
                'Voz',
                $name,
                $routes->getByName($name) !== null,
                'Rota registrada',
                'Rota ausente',
                $critical
            );
        }
    }

    private function checkVoiceConfiguration(
        array &$rows,
        int &$critical
    ): void {
        $enabled = (bool) config(
            'services.voice.enabled',
            false
        );

        $provider = (string) config(
            'services.voice.provider',
            'twilio'
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

        $validation = (bool) config(
            'services.voice.twilio.validate_webhooks',
            true
        );

        if (! $enabled) {
            $rows[] = [
                'Voz',
                strtoupper($provider),
                'SEGURO',
                $configured
                    ? 'Desativado · credenciais presentes'
                    : 'Desativado · credenciais pendentes',
            ];

            $this->check(
                $rows,
                'Voz',
                'Assinatura webhook',
                $validation,
                'Validacao ativa',
                'Validacao desativada',
                $critical
            );

            return;
        }

        $this->check(
            $rows,
            'Voz',
            strtoupper($provider),
            $configured,
            'Ativo e configurado',
            'Ativo com credenciais incompletas',
            $critical
        );

        $this->check(
            $rows,
            'Voz',
            'Assinatura webhook',
            $validation,
            'Validacao ativa',
            'Validacao desativada',
            $critical
        );
    }

    private function appendQueueSnapshot(
        array &$rows
    ): void {
        if (! Schema::hasTable(
            'confirmation_attempts'
        )) {
            return;
        }

        $queued = DB::table(
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

        $inProgress = DB::table(
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

        $failed24h = DB::table(
            'confirmation_attempts'
        )
            ->where(
                'channel',
                'voice'
            )
            ->where(
                'created_at',
                '>=',
                now()->subDay()
            )
            ->where(
                'outcome',
                'provider_failed'
            )
            ->count();

        $rows[] = [
            'Voz',
            'Fila',
            $failed24h > 0
                ? 'ATENCAO'
                : 'OK',
            "{$queued} aguardando · {$inProgress} em andamento · {$failed24h} falhas/24h",
        ];
    }

    private function appendWhatsAppSnapshot(
        array &$rows
    ): void {
        if (! Schema::hasTable(
            'wa_messages'
        )) {
            return;
        }

        $failed24h = DB::table(
            'wa_messages'
        )
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

        $lastSuccess = DB::table(
            'wa_messages'
        )
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
            ->max(
                'updated_at'
            );

        $rows[] = [
            'WhatsApp',
            'Ultimas 24h',
            $failed24h > 0
                ? 'ATENCAO'
                : 'OK',
            $failed24h
                .' falha(s) · ultimo sucesso '
                .($lastSuccess ?: '-'),
        ];
    }

    private function check(
        array &$rows,
        string $area,
        string $item,
        bool $ok,
        string $success,
        string $failure,
        int &$critical
    ): void {
        $rows[] = [
            $area,
            $item,
            $ok ? 'OK' : 'ERRO',
            $ok ? $success : $failure,
        ];

        if (! $ok) {
            $critical++;
        }
    }
}
