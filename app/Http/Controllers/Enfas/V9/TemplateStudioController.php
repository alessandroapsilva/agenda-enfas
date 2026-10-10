<?php

namespace App\Http\Controllers\Enfas\V9;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\WaTemplate;
use App\Services\Enfas\BrandMediaService;
use App\Services\Enfas\MetaWhatsAppService;
use App\Services\Enfas\V9\TemplateStudioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class TemplateStudioController extends Controller
{
    public function index(
        Request $request,
        BrandMediaService $brandMedia
    ) {
        $rows=WaTemplate::query()
            ->whereNull('archived_at')
            ->where(fn($q)=>
                $q->whereNull('name')
                  ->orWhere('name','not like','sample\_%')
            )
            ->orderByRaw("
                CASE status
                    WHEN 'APPROVED' THEN 1
                    WHEN 'PENDING' THEN 2
                    WHEN 'LOCAL' THEN 3
                    WHEN 'REJECTED' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('name')
            ->get();

        $editing=$request->integer('edit')
            ? WaTemplate::find($request->integer('edit'))
            : null;

        $brandLogo = $brandMedia
            ->defaultLogo();

        $media=DB::table('media_assets')
            ->whereNull('deleted_at')
            ->where('is_active',true)
            ->orderByRaw(
                'CASE WHEN id = ? THEN 0 ELSE 1 END',
                [$brandLogo?->id ?? 0]
            )
            ->orderBy('name')
            ->get();

        $brandName = AppSetting::getValue(
            'branding',
            'organization_name',
            ''
        ) ?: AppSetting::getValue(
            'branding',
            'system_name',
            'ENFAS Agenda'
        );

        return view(
            'enfas.v9.template-studio',
            compact(
                'rows',
                'editing',
                'media',
                'brandLogo',
                'brandName'
            )
        );
    }

    public function store(
        Request $request,
        TemplateStudioService $studio,
        BrandMediaService $brandMedia
    ) {
        $data=$this->validateData($request,true);

        if (
            ($data['header_type'] ?? 'NONE')
                === 'IMAGE'
            && empty($data['header_media_id'])
        ) {
            $data['header_media_id'] =
                $brandMedia->defaultLogo()?->id;
        }

        $data = $this->normalizeHeader(
            $data
        );

        [$keys,$samples,$buttons]=$this->arrays($data);

        $this->validateVariables(
            $data['body'],
            $keys,
            $samples
        );

        if (WaTemplate::query()
            ->where('name',$data['name'])
            ->where('language',$data['language'])
            ->exists()) {
            return back()
                ->withInput()
                ->withErrors([
                    'name'=>'Já existe um modelo com este nome e idioma.',
                ]);
        }

        $template=WaTemplate::create([
            'name'=>$data['name'],
            'purpose'=>$data['purpose'],
            'category'=>$data['category'],
            'language'=>$data['language'],
            'status'=>'LOCAL',
            'header_text'=>$data['header_text']??null,
            'body'=>$data['body'],
            'footer'=>$data['footer']??null,
            'buttons'=>$buttons,
            'variable_keys'=>$keys,
            'sample_values'=>$samples,
            'created_by'=>auth()->id(),
        ]);

        $template->forceFill([
            'header_type'=>$data['header_type'],
            'header_media_id'=>$data['header_media_id']??null,
            'is_active'=>true,
            'updated_by'=>auth()->id(),
        ])->save();

        if (($data['action']??'draft')==='send') {
            return $this->send($template,$studio);
        }

        return redirect('/whatsapp/templates')
            ->with('success','Rascunho criado.');
    }

    public function update(
        Request $request,
        WaTemplate $template,
        BrandMediaService $brandMedia
    ) {
        $data=$this->validateData($request,false);

        if (
            ($data['header_type'] ?? 'NONE')
                === 'IMAGE'
            && empty($data['header_media_id'])
        ) {
            $data['header_media_id'] =
                $brandMedia->defaultLogo()?->id;
        }

        $data = $this->normalizeHeader(
            $data
        );

        [$keys,$samples,$buttons]=$this->arrays($data);

        $this->validateVariables(
            $data['body'],
            $keys,
            $samples
        );

        /*
         * Regra de sistema corporativo:
         * o que ja foi submetido a Meta ganha NOVA REVISAO LOCAL.
         * Assim o histórico aprovado/rejeitado não é adulterado.
         */
        if (filled($template->meta_template_id)
            || in_array(
                $template->status,
                ['APPROVED','PENDING','REJECTED'],
                true
            )) {
            $revision=$template->replicate();

            $revision->forceFill([
                'name'=>Str::limit(
                    $template->name.'_v'.now()->format('His'),
                    160,
                    ''
                ),
                'status'=>'LOCAL',
                'meta_template_id'=>null,
                'rejection_reason'=>null,
                'last_error'=>null,
                'is_active'=>true,
                'archived_at'=>null,
                'created_by'=>auth()->id(),
                'updated_by'=>auth()->id(),
                'version'=>((int)($template->version??1))+1,
            ]);

            $this->apply(
                $revision,
                $data,
                $keys,
                $samples,
                $buttons
            );

            return redirect(
                '/whatsapp/templates?edit='.$revision->id
            )->with(
                'success',
                'Nova revisão criada. O histórico do modelo original foi preservado.'
            );
        }

        $this->apply(
            $template,
            $data,
            $keys,
            $samples,
            $buttons
        );

        return redirect('/whatsapp/templates')
            ->with('success','Modelo atualizado.');
    }

    public function send(
        WaTemplate $template,
        TemplateStudioService $studio
    ) {
        try {
            $data=$studio->createOnMeta($template);

            return back()->with(
                'success',
                'Modelo enviado à Meta. Status: '
                .strtoupper($data['status']??'PENDING').'.'
            );
        } catch (Throwable $e) {
            report($e);

            $template->forceFill([
                'last_error'=>$e->getMessage(),
            ])->save();

            return back()->withErrors([
                'template'=>$this->friendly($e),
            ]);
        }
    }

    public function promote(
        WaTemplate $template
    ) {
        if (
            $template->status !== 'APPROVED'
            || ! $template->is_active
            || $template->archived_at
        ) {
            return back()->withErrors([
                'promote' =>
                    'Somente um modelo aprovado, ativo e não arquivado pode entrar na régua.',
            ]);
        }

        if (! Schema::hasTable(
            'wa_automations'
        )) {
            return back()->withErrors([
                'promote' =>
                    'A estrutura de automações não está disponível.',
            ]);
        }

        $templateIds = WaTemplate::query()
            ->where(
                'purpose',
                $template->purpose
            )
            ->pluck('id')
            ->map(
                fn ($id) => (int) $id
            )
            ->all();

        if ($templateIds === []) {
            return back()->withErrors([
                'promote' =>
                    'Nenhuma automação compatível foi encontrada.',
            ]);
        }

        $columns = Schema::getColumnListing(
            'wa_automations'
        );

        $query = DB::table(
            'wa_automations'
        )
            ->where(
                'is_active',
                true
            );

        if (in_array(
            'template_id',
            $columns,
            true
        )) {
            $query->whereIn(
                'template_id',
                $templateIds
            );
        } elseif (in_array(
            'wa_template_id',
            $columns,
            true
        )) {
            $query->whereIn(
                'wa_template_id',
                $templateIds
            );
        } else {
            return back()->withErrors([
                'promote' =>
                    'A automação não possui coluna de template compatível.',
            ]);
        }

        $ids = $query
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return back()->withErrors([
                'promote' =>
                    'Não há automação ativa desta finalidade para atualizar.',
            ]);
        }

        $update = [];

        if (in_array(
            'template_id',
            $columns,
            true
        )) {
            $update['template_id'] =
                $template->id;
        }

        if (in_array(
            'wa_template_id',
            $columns,
            true
        )) {
            $update['wa_template_id'] =
                $template->id;
        }

        if (in_array(
            'updated_at',
            $columns,
            true
        )) {
            $update['updated_at'] =
                now();
        }

        DB::table('wa_automations')
            ->whereIn(
                'id',
                $ids
            )
            ->update($update);

        return back()->with(
            'success',
            count($ids)
                .' automação(ões) atualizada(s) para o modelo '
                .$template->name
                .'.'
        );
    }

    public function toggle(WaTemplate $template)
    {
        $template->forceFill([
            'is_active'=>! (bool)$template->is_active,
            'updated_by'=>auth()->id(),
        ])->save();

        return back()->with(
            'success',
            $template->is_active
                ?'Modelo ativado.'
                :'Modelo desativado e removido do uso operacional.'
        );
    }

    public function duplicate(WaTemplate $template)
    {
        $copy=$template->replicate();

        $copy->forceFill([
            'name'=>Str::limit(
                $template->name.'_copia_'.now()->format('His'),
                160,
                ''
            ),
            'status'=>'LOCAL',
            'meta_template_id'=>null,
            'rejection_reason'=>null,
            'last_error'=>null,
            'is_active'=>true,
            'archived_at'=>null,
            'created_by'=>auth()->id(),
            'updated_by'=>auth()->id(),
            'version'=>((int)($template->version??1))+1,
        ])->save();

        return redirect(
            '/whatsapp/templates?edit='.$copy->id
        )->with(
            'success',
            'Cópia criada. Agora você pode editar tudo antes de enviar.'
        );
    }

    public function brandedRevision(
        WaTemplate $template,
        BrandMediaService $brandMedia
    ) {
        $logo = $brandMedia->defaultLogo();

        if (! $logo) {
            return back()->withErrors([
                'branding' =>
                    'Cadastre um logo PNG, JPG ou WebP em Aparência antes de criar uma versão com marca.',
            ]);
        }

        $revision = $template->replicate();

        $nextVersion =
            ((int) ($template->version ?? 1))
            + 1;

        $revision->forceFill([
            'name' => Str::limit(
                $template->name
                    .'_logo_v'
                    .$nextVersion
                    .'_'
                    .now()->format('His'),
                160,
                ''
            ),
            'status' => 'LOCAL',
            'meta_template_id' => null,
            'header_type' => 'IMAGE',
            'header_media_id' => $logo->id,
            'header_text' => null,
            'rejection_reason' => null,
            'last_error' => null,
            'is_active' => true,
            'archived_at' => null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'version' => $nextVersion,
        ])->save();

        return redirect(
            '/whatsapp/templates?edit='
            .$revision->id
        )->with(
            'success',
            'Versão com logo criada. Revise a mensagem e envie para aprovação da Meta.'
        );
    }

    public function archive(WaTemplate $template)
    {
        $template->forceFill([
            'is_active'=>false,
            'archived_at'=>now(),
            'updated_by'=>auth()->id(),
        ])->save();

        return back()->with(
            'success',
            'Modelo arquivado e removido do uso operacional.'
        );
    }

    public function destroy(
        WaTemplate $template,
        MetaWhatsAppService $meta
    ) {
        try {
            $used = false;

            if (Schema::hasTable(
                'wa_automations'
            )) {
                $columns = Schema::getColumnListing(
                    'wa_automations'
                );

                $usedQuery = DB::table(
                    'wa_automations'
                );

                $usedQuery->where(
                    function ($query) use (
                        $columns,
                        $template
                    ) {
                        if (in_array(
                            'template_id',
                            $columns,
                            true
                        )) {
                            $query->orWhere(
                                'template_id',
                                $template->id
                            );
                        }

                        if (in_array(
                            'wa_template_id',
                            $columns,
                            true
                        )) {
                            $query->orWhere(
                                'wa_template_id',
                                $template->id
                            );
                        }
                    }
                );

                $used = $usedQuery->exists();
            }

            if ($used) {
                return back()->withErrors([
                    'delete'=>
                        'Este modelo está ligado a uma automação. '
                        .'Desative/alterne a automação antes de excluir.',
                ]);
            }

            if (filled($template->meta_template_id)) {
                $meta->deleteTemplate($template);

                return redirect('/whatsapp/templates')
                    ->with(
                        'success',
                        'Modelo removido da Meta e do Agenda.'
                    );
            }

            $template->delete();

            return redirect('/whatsapp/templates')
                ->with('success','Rascunho excluído.');
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'delete'=>$e->getMessage(),
            ]);
        }
    }

    public function sync(MetaWhatsAppService $meta)
    {
        try {
            $count=$meta->syncTemplates();

            return back()->with(
                'success',
                $count.' modelo(s) sincronizado(s).'
            );
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'sync'=>$e->getMessage(),
            ]);
        }
    }

    private function normalizeHeader(
        array $data
    ): array {
        $type = strtoupper(
            (string) (
                $data['header_type']
                ?? 'NONE'
            )
        );

        if ($type === 'IMAGE') {
            if (empty(
                $data['header_media_id']
            )) {
                throw ValidationException::withMessages([
                    'header_media_id' =>
                        'Selecione uma imagem ou configure o logo oficial em Aparência.',
                ]);
            }

            $activeMedia = DB::table(
                'media_assets'
            )
                ->where(
                    'id',
                    (int) $data['header_media_id']
                )
                ->where(
                    'is_active',
                    true
                )
                ->when(
                    Schema::hasColumn(
                        'media_assets',
                        'deleted_at'
                    ),
                    fn ($query) =>
                        $query->whereNull(
                            'deleted_at'
                        )
                )
                ->exists();

            if (! $activeMedia) {
                throw ValidationException::withMessages([
                    'header_media_id' =>
                        'A imagem selecionada não está disponível para uso.',
                ]);
            }

            $data['header_text'] =
                null;

            return $data;
        }

        $data['header_media_id'] =
            null;

        if ($type !== 'TEXT') {
            $data['header_text'] =
                null;
        }

        return $data;
    }

    private function apply(
        WaTemplate $template,
        array $data,
        array $keys,
        array $samples,
        array $buttons
    ): void {
        $template->forceFill([
            'purpose'=>$data['purpose'],
            'category'=>$data['category'],
            'language'=>$data['language'],
            'header_type'=>$data['header_type'],
            'header_media_id'=>$data['header_media_id']??null,
            'header_text'=>$data['header_text']??null,
            'body'=>$data['body'],
            'footer'=>$data['footer']??null,
            'variable_keys'=>$keys,
            'sample_values'=>$samples,
            'buttons'=>$buttons,
            'updated_by'=>auth()->id(),
            'version'=>$template->exists
                ? ((int)($template->version??1))+1
                : (int)($template->version??1),
            'last_error'=>null,
        ])->save();
    }

    private function validateData(
        Request $request,
        bool $withName
    ): array {
        $rules=[
            'purpose'=>[
                'required',
                'in:confirmation,reminder,reschedule,cancellation,post_service,return,medication_pickup,general',
            ],
            'category'=>[
                'required',
                'in:UTILITY,MARKETING,AUTHENTICATION',
            ],
            'language'=>[
                'required','string','max:20',
            ],
            'header_type'=>[
                'required','in:NONE,TEXT,IMAGE',
            ],
            'header_media_id'=>[
                'nullable','integer','exists:media_assets,id',
            ],
            'header_text'=>[
                'nullable','string','max:255',
            ],
            'body'=>[
                'required','string','max:1024',
            ],
            'footer'=>[
                'nullable','string','max:255',
            ],
            'variable_keys_text'=>[
                'nullable','string','max:1000',
            ],
            'sample_values_text'=>[
                'nullable','string','max:1000',
            ],
            'buttons_text'=>[
                'nullable','string','max:1000',
            ],
            'action'=>[
                'nullable','in:draft,send',
            ],
        ];

        if ($withName) {
            $rules['name']=[
                'required',
                'regex:/^[a-z0-9_]+$/',
                'max:160',
            ];
        }

        return $request->validate($rules);
    }

    private function arrays(array $data): array
    {
        $csv=fn($value)=>
            collect(explode(',',$value??''))
                ->map(fn($v)=>trim($v))
                ->filter()
                ->values()
                ->all();

        $buttons=collect(
            preg_split(
                '/\r\n|\r|\n/',
                $data['buttons_text']??''
            )
        )->map(function($line){
            [$text,$action]=array_pad(
                array_map(
                    'trim',
                    explode('|',$line,2)
                ),
                2,
                'none'
            );

            if (! $text) {
                return null;
            }

            return [
                'text'=>Str::limit($text,20,''),
                'action'=>in_array(
                    $action,
                    [
                        'confirm',
                        'cancel',
                        'reschedule',
                        'none',
                    ],
                    true
                )?$action:'none',
            ];
        })->filter()
          ->take(3)
          ->values()
          ->all();

        return [
            $csv($data['variable_keys_text']??''),
            $csv($data['sample_values_text']??''),
            $buttons,
        ];
    }

    private function validateVariables(
        string $body,
        array $keys,
        array $samples
    ): void {
        preg_match_all(
            '/\{\{(\d+)\}\}/u',
            $body,
            $matches
        );

        $indices=array_values(
            array_unique(
                array_map(
                    'intval',
                    $matches[1]??[]
                )
            )
        );

        sort($indices);

        if ($indices!==[]
            && $indices!==range(1,count($indices))) {
            abort(
                422,
                'Use variáveis sequenciais: {{1}}, {{2}}, {{3}}...'
            );
        }

        if (count($indices)!==count($keys)
            || count($indices)!==count($samples)) {
            abort(
                422,
                'Cada variável precisa de uma chave e de um exemplo.'
            );
        }
    }

    private function friendly(Throwable $e): string
    {
        $message=$e->getMessage();

        if (str_contains($message,'2388293')) {
            return
                'A Meta considerou o texto curto para a quantidade de variáveis. '
                .'Edite livremente o corpo da mensagem e tente novamente.';
        }

        if (str_contains(
            mb_strtolower($message),
            'categoria'
        )) {
            return
                'A Meta rejeitou a categoria escolhida para o conteúdo. '
                .'Edite o modelo ou crie uma nova revisão com a categoria adequada.';
        }

        return $message;
    }
}
