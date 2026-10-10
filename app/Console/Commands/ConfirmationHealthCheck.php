<?php

namespace App\Console\Commands;

use App\Services\Enfas\ConfirmationPolicyService;
use App\Services\Enfas\WhatsAppDispatchPolicy;
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
        ConfirmationPolicyService $policy,
        WhatsAppDispatchPolicy $whatsAppPolicy
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
            $rows,
            $whatsAppPolicy,
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

        foreach ([
            1440 => 'Lembrete 24 horas',
            120 => 'Lembrete 2 horas',
        ] as $offset => $label) {
            $rules = DB::table(
                'wa_automations as a'
            )
                ->join(
                    'wa_templates as t',
                    't.id',
                    '=',
                    'a.template_id'
                )
                ->where(
                    'a.is_active',
                    true
                )
                ->where(
                    'a.trigger_event',
                    'appointment_before'
                )
                ->where(
                    'a.offset_minutes',
                    $offset
                )
                ->where(
                    't.purpose',
                    'reminder'
                )
                ->where(
                    't.status',
                    'APPROVED'
                )
                ->where(
                    't.is_active',
                    true
                )
                ->whereNull(
                    't.archived_at'
                )
                ->orderBy(
                    'a.id'
                )
                ->get([
                    'a.id',
                    'a.name',
                    'a.service_id',
                    't.id as template_id',
                    't.name as template_name',
                ]);

            $valid = $rules->isNotEmpty();

            $detail = $valid
                ? $rules
                    ->map(
                        fn ($rule) =>
                            '#'
                            .$rule->id
                            .' '
                            .$rule->template_name
                            .(
                                $rule->service_id
                                    ? ' · servico '
                                        .$rule->service_id
                                    : ' · global'
                            )
                    )
                    ->implode(' | ')
                : 'Nenhuma regra ativa valida';

            $this->check(
                $rows,
                'WhatsApp',
                $label,
                $valid,
                $detail,
                $detail,
                $critical
            );
        }
    }

    private function appendAutomationConflictSnapshot(
        array &$rows,
        WhatsAppDispatchPolicy $policy,
        int &$critical
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
            ->orderBy(
                'a.id'
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

        $valid = $active->filter(
            fn ($row) =>
                $row->template_id
                && $row->template_status === 'APPROVED'
                && (bool) $row->template_active
                && $row->archived_at === null
        );

        $invalid =
            $active->count()
            - $valid->count();

        $canonicalRules = collect();
        $duplicates = 0;

        foreach (
            $valid->sortBy('id')
            as $row
        ) {
            $core = $policy
                ->automationCoreKey(
                    (string) (
                        $row->purpose
                        ?: 'general'
                    ),
                    (string) $row->trigger_event,
                    (int) $row->offset_minutes
                );

            $overlap = $canonicalRules
                ->contains(
                    function ($candidate) use (
                        $policy,
                        $core,
                        $row
                    ) {
                        return $candidate->core
                            === $core
                            && $policy
                                ->automationScopesOverlap(
                                    $candidate->service_id
                                        ? (int) $candidate->service_id
                                        : null,
                                    $row->service_id
                                        ? (int) $row->service_id
                                        : null
                                );
                    }
                );

            if ($overlap) {
                $duplicates++;
                continue;
            }

            $row->core = $core;
            $canonicalRules->push($row);
        }

        $problemCount =
            $invalid + $duplicates;

        $rows[] = [
            'WhatsApp',
            'Regras redundantes',
            $problemCount > 0
                ? 'ERRO'
                : 'OK',
            $invalid
                .' invalida(s) · '
                .$duplicates
                .' redundante(s)',
        ];

        if ($problemCount > 0) {
            $critical += $problemCount;
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
        if (! Schema::hasTable(
            'wa_messages'
        )) {
            return;
        }

        $since = now()->subDay();

        $repeatedKeys = DB::table(
            'wa_messages'
        )
            ->whereNotNull(
                'dedupe_key'
            )
            ->where(
                'created_at',
                '>=',
                $since
            )
            ->groupBy(
                'dedupe_key'
            )
            ->havingRaw(
                'COUNT(*) > 1'
            )
            ->get([
                'dedupe_key',
                DB::raw(
                    'COUNT(*) as total'
                ),
            ])
            ->count();

        $automaticWithoutKey = DB::table(
            'wa_messages'
        )
            ->where(
                'direction',
                'outbound'
            )
            ->whereNotNull(
                'automation_id'
            )
            ->whereNull(
                'dedupe_key'
            )
            ->where(
                'created_at',
                '>=',
                $since
            )
            ->whereIn(
                'status',
                [
                    'queued',
                    'sending',
                    'sent',
                    'delivered',
                    'read',
                ]
            )
            ->count();

        $manualWithoutKey = DB::table(
            'wa_messages'
        )
            ->where(
                'direction',
                'outbound'
            )
            ->whereNull(
                'automation_id'
            )
            ->whereNull(
                'dedupe_key'
            )
            ->where(
                'created_at',
                '>=',
                $since
            )
            ->whereIn(
                'status',
                [
                    'queued',
                    'sending',
                    'sent',
                    'delivered',
                    'read',
                ]
            )
            ->count();

        $problems =
            $repeatedKeys
            + $automaticWithoutKey;

        $rows[] = [
            'WhatsApp',
            'Dedupe real 24h',
            $problems > 0
                ? 'ATENCAO'
                : 'OK',
            $repeatedKeys
                .' chave(s) repetida(s) · '
                .$automaticWithoutKey
                .' automatica(s) sem chave · '
                .$manualWithoutKey
                .' manual(is) legado(s) sem chave',
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
