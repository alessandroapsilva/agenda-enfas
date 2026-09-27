<?php

namespace App\Http\Controllers\Enfas\V9;

use App\Http\Controllers\Controller;
use App\Models\WaTemplate;
use App\Services\Enfas\MetaWhatsAppService;
use App\Services\Enfas\V9\TemplateStudioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TemplateStudioController extends Controller
{
    public function index(Request $request)
    {
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

        $media=DB::table('media_assets')
            ->whereNull('deleted_at')
            ->where('is_active',true)
            ->orderBy('name')
            ->get();

        return view(
            'enfas.v9.template-studio',
            compact('rows','editing','media')
        );
    }

    public function store(
        Request $request,
        TemplateStudioService $studio
    ) {
        $data=$this->validateData($request,true);
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
        WaTemplate $template
    ) {
        $data=$this->validateData($request,false);
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
            $used=DB::table('wa_automations')
                ->where('wa_template_id',$template->id)
                ->exists();

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
            'version'=>((int)($template->version??1))+1,
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
