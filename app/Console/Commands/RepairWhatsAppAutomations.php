<?php

namespace App\Console\Commands;

use App\Services\Enfas\WhatsAppDispatchPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairWhatsAppAutomations extends Command
{
    protected $signature =
        'enfas:repair-whatsapp-automations
        {--apply : Aplica as correcoes; sem esta opcao apenas audita}';

    protected $description =
        'Audita e corrige automacoes WhatsApp invalidas ou equivalentes';

    public function handle(
        WhatsAppDispatchPolicy $policy
    ): int
    {
        if (! Schema::hasTable('wa_automations')
            || ! Schema::hasTable('wa_templates')) {
            $this->warn(
                'Estrutura de automacoes indisponivel.'
            );

            return self::SUCCESS;
        }

        $rows = DB::table('wa_automations as a')
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
            ->orderBy('a.id')
            ->get([
                'a.id',
                'a.name',
                'a.trigger_event',
                'a.offset_minutes',
                'a.service_id',
                'a.template_id',
                't.name as template_name',
                't.purpose',
                't.status as template_status',
                't.is_active as template_active',
                't.archived_at',
            ]);

        $invalid = collect();
        $valid = collect();

        foreach ($rows as $row) {
            $templateValid =
                $row->template_id
                && $row->template_status === 'APPROVED'
                && (bool) $row->template_active
                && $row->archived_at === null;

            if (! $templateValid) {
                $invalid->push($row);
                continue;
            }

            $valid->push($row);
        }

        $duplicates = collect();
        $canonicalRules = collect();

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

            $canonical = $canonicalRules
                ->first(
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

            if ($canonical) {
                $row->canonical_id =
                    $canonical->id;

                $duplicates->push($row);
                continue;
            }

            $row->core = $core;
            $canonicalRules->push($row);
        }

        $this->table(
            [
                'Tipo',
                'Regra',
                'Nome',
                'Evento',
                'Offset',
                'Template',
                'Acao',
            ],
            collect()
                ->concat(
                    $invalid->map(
                        fn ($row) => [
                            'Template invalido',
                            '#'.$row->id,
                            $row->name,
                            $row->trigger_event,
                            $row->offset_minutes,
                            $row->template_name
                                ?: '#'.$row->template_id,
                            'Pausar',
                        ]
                    )
                )
                ->concat(
                    $duplicates->map(
                        fn ($row) => [
                            'Duplicada',
                            '#'.$row->id,
                            $row->name,
                            $row->trigger_event,
                            $row->offset_minutes,
                            $row->template_name
                                ?: '#'.$row->template_id,
                            'Pausar; manter #'
                                .$row->canonical_id,
                        ]
                    )
                )
                ->all()
        );

        $ids = $invalid
            ->pluck('id')
            ->merge(
                $duplicates->pluck('id')
            )
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->info(
                'Nenhuma automacao redundante ou invalida encontrada.'
            );

            return self::SUCCESS;
        }

        if (! $this->option('apply')) {
            $this->warn(
                'Auditoria apenas. Use --apply para pausar as regras listadas.'
            );

            return self::SUCCESS;
        }

        DB::transaction(function () use ($ids) {
            DB::table('wa_automations')
                ->whereIn(
                    'id',
                    $ids->all()
                )
                ->update([
                    'is_active' => false,
                    'last_run_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        $this->info(
            $ids->count()
            .' automacao(oes) pausada(s) com seguranca.'
        );

        return self::SUCCESS;
    }
}
