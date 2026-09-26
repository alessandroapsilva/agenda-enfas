<?php
namespace App\Http\Controllers\Enfas\V8;

use App\Http\Controllers\Controller;
use App\Models\WaTemplate;
use App\Services\Enfas\V8\MetaMediaTemplateService;
use App\Services\Enfas\V8\TemplateComposer;
use App\Services\Enfas\V8\TemplateCategoryRecovery;
use App\Services\Enfas\V8\V8Support;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TemplatePremiumController extends Controller
{
    public function index(Request $r)
    {
        $rows=WaTemplate::query()
            ->where(fn($q)=>$q->whereNull('name')->orWhere('name','not like','sample\_%'))
            ->whereNull('archived_at')
            ->orderByRaw("CASE status WHEN 'APPROVED' THEN 1 WHEN 'PENDING' THEN 2 WHEN 'LOCAL' THEN 3 WHEN 'REJECTED' THEN 4 ELSE 5 END")
            ->orderBy('name')->get();
        $media=DB::table('media_assets')->whereNull('deleted_at')->where('is_active',true)->orderBy('name')->get();
        $editing=$r->integer('edit')?WaTemplate::find($r->integer('edit')):null;
        return view('enfas.v8.templates',compact('rows','media','editing'));
    }

    public function update(Request $r,WaTemplate $template,V8Support $s)
    {
        if(!in_array($template->status,['LOCAL','REJECTED'],true)){
            return back()->withErrors(['template'=>'Modelos já enviados à Meta não são editados diretamente. Duplique o modelo para criar uma nova versão.']);
        }

        $d=$r->validate([
            'purpose'=>['required','string','max:40'],'category'=>['required','in:UTILITY,MARKETING,AUTHENTICATION'],
            'header_type'=>['required','in:NONE,TEXT,IMAGE'],'header_text'=>['nullable','string','max:255'],
            'header_media_id'=>['nullable','integer'],'body'=>['required','string','max:1024'],
            'footer'=>['nullable','string','max:255'],'variable_keys_text'=>['nullable','string','max:1000'],
            'sample_values_text'=>['nullable','string','max:1000'],'buttons_text'=>['nullable','string','max:1000'],
        ]);

        $csv=fn($v)=>collect(explode(',',$v??''))->map(fn($x)=>trim($x))->filter()->values()->all();
        $buttons=collect(preg_split('/\r\n|\r|\n/',$d['buttons_text']??''))->map(function($line){
            [$text,$action]=array_pad(array_map('trim',explode('|',$line,2)),2,'none');
            return $text?['text'=>Str::limit($text,20,''),'action'=>$action]:null;
        })->filter()->take(3)->values()->all();

        $template->forceFill([
            'purpose'=>$d['purpose'],'category'=>$d['category'],'header_type'=>$d['header_type'],
            'header_text'=>$d['header_text']??null,'header_media_id'=>$d['header_media_id']??null,
            'body'=>$d['body'],'footer'=>$d['footer']??null,'variable_keys'=>$csv($d['variable_keys_text']??''),
            'sample_values'=>$csv($d['sample_values_text']??''),'buttons'=>$buttons,
            'updated_by'=>auth()->id(),'version'=>((int)($template->version??1))+1,'last_error'=>null
        ])->save();
        $s->audit('templates','update',$template->id,'Modelo editado');
        return redirect('/premium/whatsapp/modelos')->with('success','Modelo atualizado.');
    }

    public function duplicate(WaTemplate $template,V8Support $s)
    {
        $c=$template->replicate();
        $c->name=Str::limit($template->name.'_v'.now()->format('His'),160,'');
        $c->status='LOCAL'; $c->meta_template_id=null; $c->rejection_reason=null;
        $c->last_error=null; $c->is_active=true; $c->archived_at=null;
        $c->created_by=auth()->id(); $c->updated_by=auth()->id(); $c->save();
        $s->audit('templates','duplicate',$c->id,'Nova versão criada');
        return redirect('/premium/whatsapp/modelos?edit='.$c->id)->with('success','Nova versão criada. Agora você pode editar.');
    }

    public function recoverCategory(
        WaTemplate $template,
        TemplateCategoryRecovery $recovery,
        V8Support $support
    ) {
        if ($template->status !== 'REJECTED') {
            return back()->withErrors([
                'template' => 'A recuperação de categoria é destinada a modelos rejeitados.',
            ]);
        }

        $copy = $recovery->recover($template);

        $support->audit(
            'templates',
            'category_recovery',
            $copy->id,
            'Nova versão criada após rejeição de categoria'
        );

        return redirect('/premium/whatsapp/modelos?edit='.$copy->id)
            ->with('success','Nova versão criada com texto transacional e categoria adequada. Revise e envie novamente.');
    }

    public function send(WaTemplate $template,TemplateComposer $composer,MetaMediaTemplateService $meta,V8Support $s)
    {
        try{
            $template=$composer->normalize($template);
            $d=$meta->create($template);
            $s->audit('templates','submit',$template->id,'Modelo enviado à Meta');
            return back()->with('success','Modelo enviado para a Meta. Status: '.strtoupper($d['status']??'PENDING').'.');
        }catch(Throwable $e){
            report($e);
            $template->forceFill(['last_error'=>$e->getMessage()])->save();
            $msg=str_contains($e->getMessage(),'2388293')
                ?'A Meta ainda considerou o texto curto. Use “Corrigir texto” e envie novamente. O rascunho foi mantido.'
                :$e->getMessage();
            return back()->withErrors(['template'=>$msg]);
        }
    }
}
