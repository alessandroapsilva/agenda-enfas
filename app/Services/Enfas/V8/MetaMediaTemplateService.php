<?php
namespace App\Services\Enfas\V8;

use App\Models\MetaIntegration;
use App\Models\WaTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MetaMediaTemplateService
{
    private function integration(): MetaIntegration
    {
        return MetaIntegration::query()->firstOrFail();
    }

    private function base($i): string
    {
        return 'https://graph.facebook.com/'.($i->graph_version?:'v25.0');
    }

    private function error($r,string $fallback): RuntimeException
    {
        $e=$r->json('error')??[];
        return new RuntimeException(
            ($e['error_user_title']??$e['error_user_msg']??$e['message']??$fallback)
            .(isset($e['code'])?' [code '.$e['code'].']':'')
            .(isset($e['error_subcode'])?', subcode '.$e['error_subcode']:'')
        );
    }

    private function mediaHandle(int $mediaId): string
    {
        $m=DB::table('media_assets')->where('id',$mediaId)->where('is_active',true)->first();
        if(!$m)throw new RuntimeException('Imagem não encontrada ou desativada.');

        if($m->meta_handle && $m->meta_handle_at && \Illuminate\Support\Carbon::parse($m->meta_handle_at)->gt(now()->subHours(12))){
            return $m->meta_handle;
        }

        $i=$this->integration();
        $file=Storage::disk($m->disk?:'public')->path($m->path);
        if(!is_file($file))throw new RuntimeException('Arquivo de mídia não encontrado no storage.');

        $session=Http::withToken($i->access_token)->acceptJson()->timeout(35)
            ->withQueryParameters([
                'file_length'=>filesize($file),
                'file_type'=>$m->mime_type,
                'file_name'=>basename($file),
            ])->post($this->base($i).'/app/uploads');

        if(!$session->successful())throw $this->error($session,'A Meta recusou a sessão de upload.');
        $uploadId=$session->json('id');
        if(!$uploadId)throw new RuntimeException('A Meta não retornou o ID de upload.');

        $upload=Http::withToken($i->access_token)->acceptJson()->timeout(45)
            ->withHeaders(['file_offset'=>'0','Content-Type'=>$m->mime_type])
            ->withBody(file_get_contents($file),$m->mime_type)
            ->post($this->base($i).'/'.$uploadId);

        if(!$upload->successful())throw $this->error($upload,'A Meta recusou o upload da imagem.');
        $handle=$upload->json('h');
        if(!$handle)throw new RuntimeException('A Meta não retornou o handle da imagem.');

        DB::table('media_assets')->where('id',$mediaId)->update([
            'meta_handle'=>$handle,'meta_handle_at'=>now(),'updated_at'=>now()
        ]);
        return $handle;
    }

    public function components(WaTemplate $t): array
    {
        $c=[]; $type=strtoupper($t->header_type?:'TEXT');

        if($type==='IMAGE'){
            if(!$t->header_media_id)throw new RuntimeException('Escolha uma imagem para o cabeçalho.');
            $c[]=['type'=>'HEADER','format'=>'IMAGE','example'=>['header_handle'=>[$this->mediaHandle((int)$t->header_media_id)]]];
        }elseif($type==='TEXT' && filled($t->header_text)){
            $c[]=['type'=>'HEADER','format'=>'TEXT','text'=>$t->header_text];
        }

        $body=['type'=>'BODY','text'=>$t->body];
        if(($t->sample_values??[])!==[])$body['example']=['body_text'=>[array_values($t->sample_values)]];
        $c[]=$body;

        if(filled($t->footer))$c[]=['type'=>'FOOTER','text'=>$t->footer];

        $buttons=collect($t->buttons??[])->take(3)->map(fn($b)=>[
            'type'=>'QUICK_REPLY','text'=>$b['text']??'Responder'
        ])->values()->all();
        if($buttons)$c[]=['type'=>'BUTTONS','buttons'=>$buttons];

        return $c;
    }

    public function create(WaTemplate $t): array
    {
        $i=$this->integration();
        $r=Http::withToken($i->access_token)->acceptJson()->asJson()->timeout(40)
            ->post($this->base($i).'/'.$i->waba_id.'/message_templates',[
                'name'=>$t->name,'language'=>$t->language,'category'=>$t->category,
                'components'=>$this->components($t),
            ]);
        if(!$r->successful())throw $this->error($r,'A Meta recusou a criação do modelo.');

        $d=$r->json();
        $t->forceFill([
            'meta_template_id'=>$d['id']??null,'status'=>$d['status']??'PENDING',
            'category'=>$d['category']??$t->category,'rejection_reason'=>null,
            'last_error'=>null,'last_synced_at'=>now()
        ])->save();
        return $d;
    }
}
