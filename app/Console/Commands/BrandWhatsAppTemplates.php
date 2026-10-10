<?php

namespace App\Console\Commands;

use App\Models\WaTemplate;
use App\Services\Enfas\BrandMediaService;
use App\Services\Enfas\V9\TemplateStudioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class BrandWhatsAppTemplates extends Command
{
    protected $signature =
        'enfas:brand-whatsapp-templates
        {--apply : Cria revisoes locais com o logo oficial}
        {--submit : Envia revisoes locais criadas/encontradas para a Meta}
        {--promote-approved : Coloca versoes aprovadas com logo nas automacoes ativas}';

    protected $description =
        'Prepara e promove modelos WhatsApp com a identidade visual oficial';

    public function handle(
        BrandMediaService $brandMedia,
        TemplateStudioService $studio
    ): int {
        if (! Schema::hasTable('wa_templates')
            || ! Schema::hasTable('wa_automations')) {
            $this->error(
                'Estrutura de WhatsApp indisponivel.'
            );

            return self::FAILURE;
        }

        $logo = $brandMedia->defaultLogo();

        if (! $logo) {
            $this->warn(
                'Nenhum logo PNG/JPG/WebP configurado. Cadastre-o em Aparencia.'
            );

            return self::SUCCESS;
        }

        $columns = Schema::getColumnListing(
            'wa_automations'
        );

        $templateColumn = in_array(
            'template_id',
            $columns,
            true
        )
            ? 'template_id'
            : (
                in_array(
                    'wa_template_id',
                    $columns,
                    true
                )
                    ? 'wa_template_id'
                    : null
            );

        if (! $templateColumn) {
            $this->error(
                'A tabela de automacoes nao possui coluna de template.'
            );

            return self::FAILURE;
        }

        $sourceIds = DB::table(
            'wa_automations'
        )
            ->where(
                'is_active',
                true
            )
            ->whereNotNull(
                $templateColumn
            )
            ->pluck(
                $templateColumn
            )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();

        if ($sourceIds->isEmpty()) {
            $this->info(
                'Nenhuma automacao ativa usa modelos.'
            );

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($sourceIds as $sourceId) {
            $source = WaTemplate::find(
                $sourceId
            );

            if (! $source) {
                $rows[] = [
                    '#'.$sourceId,
                    '-',
                    'IGNORADO',
                    'Modelo de origem nao encontrado',
                ];

                continue;
            }

            if (
                strtoupper(
                    (string) $source->header_type
                ) === 'IMAGE'
                && (int) $source->header_media_id
                    === (int) $logo->id
            ) {
                $rows[] = [
                    '#'.$source->id,
                    $source->name,
                    'OK',
                    'Ja usa o logo oficial',
                ];

                continue;
            }

            $branded = WaTemplate::query()
                ->where(
                    'purpose',
                    $source->purpose
                )
                ->where(
                    'language',
                    $source->language
                )
                ->where(
                    'category',
                    $source->category
                )
                ->where(
                    'body',
                    $source->body
                )
                ->where(
                    'header_type',
                    'IMAGE'
                )
                ->where(
                    'header_media_id',
                    $logo->id
                )
                ->whereNull(
                    'archived_at'
                )
                ->orderByDesc('id')
                ->first();

            if (! $branded
                && $this->option('apply')) {
                $branded = $source
                    ->replicate();

                $nextVersion =
                    ((int) (
                        $source->version
                        ?? 1
                    ))
                    + 1;

                $baseName = Str::limit(
                    $source->name
                        .'_logo_v'
                        .$nextVersion,
                    145,
                    ''
                );

                $name = $baseName;
                $suffix = 1;

                while (
                    WaTemplate::query()
                        ->where(
                            'name',
                            $name
                        )
                        ->where(
                            'language',
                            $source->language
                        )
                        ->exists()
                ) {
                    $name = Str::limit(
                        $baseName
                            .'_'
                            .$suffix,
                        160,
                        ''
                    );

                    $suffix++;
                }

                $branded->forceFill([
                    'name' => $name,
                    'status' => 'LOCAL',
                    'meta_template_id' => null,
                    'header_type' => 'IMAGE',
                    'header_media_id' =>
                        $logo->id,
                    'header_text' => null,
                    'rejection_reason' => null,
                    'last_error' => null,
                    'is_active' => true,
                    'archived_at' => null,
                    'created_by' => null,
                    'updated_by' => null,
                    'version' => $nextVersion,
                ])->save();
            }

            if (! $branded) {
                $rows[] = [
                    '#'.$source->id,
                    $source->name,
                    'PENDENTE',
                    'Criaria revisao com logo',
                ];

                continue;
            }

            if (
                $this->option('submit')
                && $branded->status === 'LOCAL'
                && blank(
                    $branded->meta_template_id
                )
            ) {
                try {
                    $studio->createOnMeta(
                        $branded
                    );

                    $branded->refresh();
                } catch (Throwable $e) {
                    report($e);

                    $branded->forceFill([
                        'last_error' =>
                            $e->getMessage(),
                    ])->save();

                    $rows[] = [
                        '#'.$branded->id,
                        $branded->name,
                        'ERRO META',
                        Str::limit(
                            $e->getMessage(),
                            100
                        ),
                    ];

                    continue;
                }
            }

            $promoted = 0;

            if (
                $this->option(
                    'promote-approved'
                )
                && $branded->status
                    === 'APPROVED'
            ) {
                $update = [
                    $templateColumn =>
                        $branded->id,
                ];

                if (in_array(
                    'updated_at',
                    $columns,
                    true
                )) {
                    $update['updated_at'] =
                        now();
                }

                $promoted = DB::table(
                    'wa_automations'
                )
                    ->where(
                        'is_active',
                        true
                    )
                    ->where(
                        $templateColumn,
                        $source->id
                    )
                    ->update($update);
            }

            $detail = match (
                $branded->status
            ) {
                'APPROVED' =>
                    $promoted > 0
                        ? $promoted
                            .' automacao(oes) promovida(s)'
                        : 'Aprovado com logo',
                'PENDING' =>
                    'Aguardando aprovacao da Meta',
                'REJECTED' =>
                    'Rejeitado: revise no Studio',
                default =>
                    'Revisao local pronta',
            };

            $rows[] = [
                '#'.$branded->id,
                $branded->name,
                $branded->status,
                $detail,
            ];
        }

        $this->table(
            [
                'Modelo',
                'Nome',
                'Estado',
                'Detalhe',
            ],
            $rows
        );

        if (! $this->option('apply')) {
            $this->newLine();

            $this->line(
                'Use --apply para criar as revisoes locais.'
            );
        }

        return self::SUCCESS;
    }
}
