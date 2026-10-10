<?php

namespace App\Console\Commands;

use App\Services\Enfas\ConfirmationPolicyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConfirmationHealthCheck extends Command
{
    protected $signature =
        'enfas:confirmation-health';

    protected $description =
        'Valida a saude operacional da central de confirmacoes';

    public function handle(
        ConfirmationPolicyService $policy
    ): int
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

        $this->appendAutomationConflictSnapshot(
            $rows
        );

        $this->checkVoiceRoutes(
            $rows,
            $critical
        );

        $this->checkVoiceConfiguration(
            $rows,
            $critical
        );

        $this->appendPolicySnapshot(
            $rows,
            $policy
        );

        $this->appendQueueSnapshot(
            $rows
        );

        $this->appendWhatsAppSnapshot(
            $rows
        );

        $this->appendDuplicateSnapshot(
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

    private function appendAutomationConflictSnapshot(
        array &$rows
    ): void {
        if (! Schema::hasTable('wa_automations')
            || ! Schema::hasTable('wa_templates')) {
            return;
        }

        $active = DB::table(
            'wa_automations as a'
        )
            ->leftJoin(
                'wa_templates as t',
                't.id',
                '=',
                'a.template_id'
            )
            ->where(
                'a.is_active',
                true
            )
            ->get([
                'a.id',
                'a.trigger_event',
                'a.offset_minutes',
                'a.service_id',
                'a.template_id',
                't.purpose',
                't.status as template_status',
                't.is_active as template_active',
                't.archived_at',
            ]);

        $invalid = $active->filter(
            fn ($row) =>
                ! $row->template_id
                || $row->template_status !== 'APPROVED'
                || ! (bool) $row->template_active
                || $row->archived_at !== null
        )->count();

        $duplicateGroups = $active
            ->filter(
                fn ($row) =>
                    $row->template_id
                    && $row->template_status === 'APPROVED'
                    && (bool) $row->template_active
                    && $row->archived_at === null
            )
            ->groupBy(
                fn ($row) =>
                    $row->trigger_event
                    .'|'
                    .(int) $row->offset_minutes
                    .'|'
                    .($row->service_id ?: 0)
                    .'|'
                    .strtolower(
                        (string) (
                            $row->purpose
                            ?: 'general'
                        )
                    )
            )
            ->filter(
                fn ($group) =>
                    $group->count() > 1
            )
            ->count();

        $rows[] = [
            'WhatsApp',
            'Regras redundantes',
            ($invalid + $duplicateGroups) > 0
                ? 'ATENCAO'
                : 'OK',
            $invalid
                .' invalida(s) · '
                .$duplicateGroups
                .' grupo(s) duplicado(s)',
        ];
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

    private function appendPolicySnapshot(
        array &$rows,
        ConfirmationPolicyService $policy
    ): void {
        $summary = $policy->summary();

        $rows[] = [
            'Regua',
            'Fallback voz',
            $summary['voice_fallback_enabled']
                ? 'ATIVO'
                : 'PAUSADO',
            'Escalada em '
                .$summary['voice_escalation_minutes']
                .'min · sem WhatsApp em '
                .$summary['voice_no_whatsapp_minutes']
                .'min',
        ];

        $rows[] = [
            'Regua',
            'Janela de ligacao',
            'OK',
            $summary['voice_allowed_start']
                .'–'
                .$summary['voice_allowed_end'],
        ];

        $rows[] = [
            'Regua',
            'Tentativas',
            'OK',
            'max '
                .$summary['voice_max_attempts']
                .' · retry '
                .$summary['voice_retry_minutes']
                .'min',
        ];

        $rows[] = [
            'Regua',
            'Consentimento',
            $summary['respect_contact_consent']
                ? 'OK'
                : 'ATENCAO',
            $summary['respect_contact_consent']
                ? 'Consentimento e opt-out respeitados'
                : 'Regra de consentimento desativada',
        ];

        $rows[] = [
            'Regua',
            'Fallback humano',
            $summary['human_fallback_enabled']
                ? 'ATIVO'
                : 'PAUSADO',
            $summary['human_fallback_enabled']
                ? 'Tarefa operacional apos esgotamento'
                : 'Sem criacao automatica de tarefa',
        ];

        $professionalCount = count(
            $summary['voice_professional_ids']
        );

        $serviceCount = count(
            $summary['voice_service_ids']
        );

        $rows[] = [
            'Regua',
            'Escopo',
            'OK',
            ($professionalCount > 0
                ? $professionalCount.' profissional(is)'
                : 'todos profissionais')
            .' · '
            .($serviceCount > 0
                ? $serviceCount.' servico(s)'
                : 'todos servicos'),
        ];
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

    private function appendDuplicateSnapshot(
        array &$rows
    ): void {
        if (! Schema::hasTable('wa_messages')
            || ! Schema::hasTable('wa_templates')) {
            return;
        }

        $messages = DB::table('wa_messages as wm')
            ->leftJoin(
                'wa_templates as wt',
                'wt.id',
                '=',
                'wm.template_id'
            )
            ->where(
                'wm.direction',
                'outbound'
            )
            ->where(
                'wm.created_at',
                '>=',
                now()->subDay()
            )
            ->whereIn(
                'wm.status',
                [
                    'queued',
                    'sending',
                    'sent',
                    'delivered',
                    'read',
                ]
            )
            ->orderBy('wm.created_at')
            ->get([
                'wm.id',
                'wm.appointment_id',
                'wm.patient_id',
                'wm.automation_id',
                'wm.body',
                'wm.created_at',
                'wt.purpose',
            ]);

        $semanticDuplicates = 0;

        $messages
            ->filter(
                fn ($row) =>
                    $row->automation_id
                    && $row->appointment_id
            )
            ->groupBy(
                fn ($row) =>
                    $row->appointment_id
                    .'|'
                    .($row->purpose ?: 'general')
            )
            ->each(function ($group) use (
                &$semanticDuplicates
            ) {
                $previous = null;

                foreach ($group as $row) {
                    $at = \Illuminate\Support\Carbon::parse(
                        $row->created_at
                    );

                    if ($previous
                        && $previous->diffInMinutes(
                            $at
                        ) <= 10) {
                        $semanticDuplicates++;
                    }

                    $previous = $at;
                }
            });

        $manualDuplicates = 0;

        $messages
            ->filter(
                fn ($row) =>
                    ! $row->automation_id
                    && filled($row->body)
            )
            ->groupBy(
                fn ($row) =>
                    ($row->appointment_id
                        ?: 'patient-'.$row->patient_id)
                    .'|'
                    .sha1(
                        trim(
                            (string) $row->body
                        )
                    )
            )
            ->each(function ($group) use (
                &$manualDuplicates
            ) {
                $previous = null;

                foreach ($group as $row) {
                    $at = \Illuminate\Support\Carbon::parse(
                        $row->created_at
                    );

                    if ($previous
                        && $previous->diffInMinutes(
                            $at
                        ) <= 2) {
                        $manualDuplicates++;
                    }

                    $previous = $at;
                }
            });

        $total = $semanticDuplicates
            + $manualDuplicates;

        $rows[] = [
            'WhatsApp',
            'Duplicidade 24h',
            $total > 0
                ? 'ATENCAO'
                : 'OK',
            $semanticDuplicates
                .' automatica(s) suspeita(s) · '
                .$manualDuplicates
                .' manual(is) suspeita(s)',
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
