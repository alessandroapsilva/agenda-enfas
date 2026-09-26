<?php
namespace App\Http\Controllers\Enfas\V8;

use App\Http\Controllers\Controller;
use App\Models\WaTemplate;
use App\Services\Enfas\V8\TemplateComposer;
use App\Services\Enfas\V8\V8Support;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PremiumController extends Controller
{
    public function dashboard()
    {
        $date=$this->dateColumn();
        $metrics=[
            'today'=>$date?DB::table('appointments')->whereDate($date,today())->count():0,
            'patients'=>DB::table('patients')->count(),
            'professionals'=>DB::table('professionals')->count(),
            'services'=>DB::table('services')->count(),
            'approved'=>Schema::hasTable('wa_templates')?DB::table('wa_templates')->where('status','APPROVED')->where('is_active',true)->count():0,
            'pending'=>Schema::hasTable('wa_templates')?DB::table('wa_templates')->where('status','PENDING')->count():0,
        ];
        return view('enfas.v8.dashboard',compact('metrics'));
    }

    public function professionals(Request $r)
    {
        return $this->crud('professionals','Profissionais','Equipe, especialidades, registros e agenda.',[
            ['name','Nome completo','text'],['email','E-mail','email'],['phone','Telefone/WhatsApp','text'],
            ['specialty','Especialidade','text'],['registration_type','Conselho','text'],
            ['registration_number','Número do registro','text'],['color','Cor na agenda','color'],
            ['notes','Observações','textarea']
        ],$r);
    }

    public function patients(Request $r)
    {
        return $this->crud('patients','Pacientes','Cadastro, WhatsApp e histórico administrativo.',[
            ['name','Nome completo','text'],['phone','Telefone/WhatsApp','text'],['email','E-mail','email'],
            ['cpf','CPF','text'],['birth_date','Nascimento','date'],['notes','Observações','textarea']
        ],$r);
    }

    public function services(Request $r)
    {
        return $this->crud('services','Serviços','Duração, valor, preparo e status.',[
            ['name','Nome do serviço','text'],['duration_minutes','Duração (min)','number'],
            ['price','Valor','number'],['color','Cor na agenda','color'],
            ['description','Descrição','textarea'],['preparation','Orientações/preparo','textarea']
        ],$r);
    }

    public function storeMaster(Request $r,string $table,V8Support $s)
    {
        abort_unless(in_array($table,['professionals','patients','services'],true),404);
        $rules=['name'=>['required','string','max:255']];
        foreach(['email','phone','specialty','registration_type','registration_number','color','notes','cpf','birth_date','description','preparation','duration_minutes','price'] as $f){
            $rules[$f]=['nullable'];
        }
        $d=$r->validate($rules);
        if($table==='services' && empty($d['duration_minutes']))$d['duration_minutes']=30;
        $payload=$s->cols($table,$d)+$s->active($table,true)+$s->cols($table,['created_at'=>now(),'updated_at'=>now()]);
        $id=DB::table($table)->insertGetId($payload);
        $s->audit($table,'create',$id,'Cadastro criado');
        return back()->with('success','Cadastro criado com sucesso.');
    }

    public function updateMaster(Request $r,string $table,int $id,V8Support $s)
    {
        abort_unless(in_array($table,['professionals','patients','services'],true),404);
        abort_unless(DB::table($table)->where('id',$id)->exists(),404);
        $d=$r->except(['_token','_method']);
        $payload=$s->cols($table,$d+['updated_at'=>now()]);
        DB::table($table)->where('id',$id)->update($payload);
        $s->audit($table,'update',$id,'Cadastro atualizado');
        return redirect($this->masterUrl($table))->with('success','Cadastro atualizado.');
    }

    public function toggleMaster(string $table,int $id,V8Support $s)
    {
        abort_unless(in_array($table,['professionals','patients','services'],true),404);
        $row=DB::table($table)->find($id); abort_unless($row,404);
        $current=(bool)($row->is_active??$row->active??true);
        DB::table($table)->where('id',$id)->update($s->active($table,!$current)+$s->cols($table,['updated_at'=>now()]));
        $s->audit($table,'toggle',$id,$current?'Desativado':'Ativado');
        return back()->with('success',$current?'Cadastro desativado.':'Cadastro ativado.');
    }

    private function crud(string $table,string $title,string $subtitle,array $fields,Request $r)
    {
        $rows=DB::table($table)->orderBy('name')->get();
        $editing=$r->integer('edit')?DB::table($table)->find($r->integer('edit')):null;
        return view('enfas.v8.crud',compact('table','title','subtitle','fields','rows','editing'));
    }

    private function masterUrl(string $table): string
    {
        return match($table){
            'professionals'=>'/premium/profissionais',
            'patients'=>'/premium/pacientes',
            'services'=>'/premium/servicos',
            default=>'/premium'
        };
    }

    public function locations()
    {
        return view('enfas.v8.locations',['rows'=>DB::table('locations')->orderBy('name')->get()]);
    }

    public function storeLocation(Request $r,V8Support $s)
    {
        $d=$r->validate(['name'=>['required','string','max:255'],'phone'=>['nullable'],'email'=>['nullable'],'address'=>['nullable'],'city'=>['nullable'],'state'=>['nullable'],'postal_code'=>['nullable'],'notes'=>['nullable']]);
        $id=DB::table('locations')->insertGetId($d+['is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $s->audit('locations','create',$id,'Unidade cadastrada');
        return back()->with('success','Unidade cadastrada.');
    }

    public function toggleLocation(int $id,V8Support $s)
    {
        $row=DB::table('locations')->find($id); abort_unless($row,404);
        DB::table('locations')->where('id',$id)->update(['is_active'=>!$row->is_active,'updated_at'=>now()]);
        $s->audit('locations','toggle',$id,'Status alterado');
        return back()->with('success','Status da unidade atualizado.');
    }

    public function availability()
    {
        return view('enfas.v8.availability',[
            'professionals'=>DB::table('professionals')->orderBy('name')->get(),
            'locations'=>DB::table('locations')->where('is_active',true)->orderBy('name')->get(),
            'rules'=>DB::table('professional_availabilities as a')->join('professionals as p','p.id','=','a.professional_id')->leftJoin('locations as l','l.id','=','a.location_id')->select('a.*','p.name as professional_name','l.name as location_name')->orderBy('p.name')->orderBy('a.weekday')->get(),
            'absences'=>DB::table('professional_absences as a')->join('professionals as p','p.id','=','a.professional_id')->select('a.*','p.name as professional_name')->orderByDesc('a.starts_at')->limit(100)->get(),
        ]);
    }

    public function storeAvailability(Request $r,V8Support $s)
    {
        $d=$r->validate(['professional_id'=>['required','integer'],'location_id'=>['nullable','integer'],'weekday'=>['required','integer','between:0,6'],'starts_at'=>['required'],'ends_at'=>['required'],'slot_minutes'=>['required','integer','between:5,240']]);
        $id=DB::table('professional_availabilities')->insertGetId($d+['is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $s->audit('availability','create',$id,'Disponibilidade criada');
        return back()->with('success','Disponibilidade cadastrada.');
    }

    public function deleteAvailability(int $id,V8Support $s)
    {
        DB::table('professional_availabilities')->where('id',$id)->delete();
        $s->audit('availability','delete',$id,'Disponibilidade removida');
        return back()->with('success','Disponibilidade removida.');
    }

    public function storeAbsence(Request $r,V8Support $s)
    {
        $d=$r->validate(['professional_id'=>['required','integer'],'starts_at'=>['required','date'],'ends_at'=>['required','date','after:starts_at'],'reason'=>['nullable','string','max:255']]);
        $id=DB::table('professional_absences')->insertGetId($d+['is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $s->audit('availability','absence_create',$id,'Ausência cadastrada');
        return back()->with('success','Ausência/bloqueio cadastrado.');
    }

    public function deleteAbsence(int $id,V8Support $s)
    {
        DB::table('professional_absences')->where('id',$id)->delete();
        $s->audit('availability','absence_delete',$id,'Ausência removida');
        return back()->with('success','Ausência removida.');
    }

    public function media()
    {
        $rows=DB::table('media_assets')->whereNull('deleted_at')->orderByDesc('id')->get();
        return view('enfas.v8.media',compact('rows'));
    }

    public function storeMedia(Request $r,V8Support $s)
    {
        $d=$r->validate(['name'=>['required','string','max:255'],'file'=>['required','image','mimes:jpg,jpeg,png,webp','max:8192']]);
        $f=$r->file('file'); $name=Str::uuid().'.'.strtolower($f->getClientOriginalExtension());
        $path=$f->storeAs('whatsapp/media',$name,'public');
        $id=DB::table('media_assets')->insertGetId([
            'name'=>$d['name'],'kind'=>'image','disk'=>'public','path'=>$path,
            'mime_type'=>$f->getMimeType()?:'image/jpeg','size'=>$f->getSize()?:0,
            'is_active'=>true,'uploaded_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()
        ]);
        $s->audit('media','upload',$id,'Imagem adicionada');
        return back()->with('success','Imagem adicionada à biblioteca.');
    }

    public function toggleMedia(int $id,V8Support $s)
    {
        $row=DB::table('media_assets')->find($id); abort_unless($row,404);
        DB::table('media_assets')->where('id',$id)->update(['is_active'=>!$row->is_active,'updated_at'=>now()]);
        $s->audit('media','toggle',$id,'Status da mídia alterado');
        return back()->with('success','Status atualizado.');
    }

    public function deleteMedia(int $id,V8Support $s)
    {
        $row=DB::table('media_assets')->find($id); abort_unless($row,404);
        Storage::disk($row->disk?:'public')->delete($row->path);
        DB::table('media_assets')->where('id',$id)->update(['deleted_at'=>now(),'is_active'=>false,'updated_at'=>now()]);
        $s->audit('media','delete',$id,'Mídia excluída');
        return back()->with('success','Mídia excluída.');
    }

    public function templates()
    {
        $rows=WaTemplate::query()->where(fn($q)=>$q->whereNull('name')->orWhere('name','not like','sample\_%'))->whereNull('archived_at')->orderByRaw("CASE status WHEN 'APPROVED' THEN 1 WHEN 'PENDING' THEN 2 WHEN 'LOCAL' THEN 3 WHEN 'REJECTED' THEN 4 ELSE 5 END")->get();
        return view('enfas.v8.templates',compact('rows'));
    }

    public function toggleTemplate(WaTemplate $template,V8Support $s)
    {
        $template->forceFill(['is_active'=>!$template->is_active,'updated_by'=>auth()->id()])->save();
        $s->audit('templates','toggle',$template->id,$template->is_active?'Modelo ativado':'Modelo desativado');
        return back()->with('success',$template->is_active?'Modelo ativado.':'Modelo desativado e fora das novas automações.');
    }

    public function autoFixTemplate(WaTemplate $template,TemplateComposer $c,V8Support $s)
    {
        $before=$template->body; $c->normalize($template);
        $s->audit('templates','autofix',$template->id,'Texto reforçado automaticamente');
        return back()->with('success',$before===$template->fresh()->body?'Texto já possuía margem suficiente.':'Texto reforçado. Agora tente enviar para a Meta novamente.');
    }

    public function archiveTemplate(WaTemplate $template,V8Support $s)
    {
        $template->forceFill(['is_active'=>false,'archived_at'=>now(),'updated_by'=>auth()->id()])->save();
        $s->audit('templates','archive',$template->id,'Modelo arquivado');
        return back()->with('success','Modelo arquivado e removido do uso operacional.');
    }

    public function reports(Request $r)
    {
        $date=$this->dateColumn(); $from=$r->date('from')??now()->startOfMonth(); $to=$r->date('to')??now()->endOfMonth();
        $metrics=['appointments'=>0,'confirmed'=>0,'cancelled'=>0,'attended'=>0,'no_show'=>0];
        if($date){
            $base=DB::table('appointments')->whereBetween($date,[$from->copy()->startOfDay(),$to->copy()->endOfDay()]);
            $metrics['appointments']=(clone $base)->count();
            foreach(['confirmed_at'=>'confirmed','cancelled_at'=>'cancelled','attended_at'=>'attended','no_show_at'=>'no_show'] as $col=>$key)if(Schema::hasColumn('appointments',$col))$metrics[$key]=(clone $base)->whereNotNull($col)->count();
        }
        return view('enfas.v8.reports',compact('metrics','from','to'));
    }

    public function audit()
    {
        return view('enfas.v8.audit',['rows'=>DB::table('audit_logs')->orderByDesc('id')->limit(500)->get()]);
    }

    public function settings()
    {
        return view('enfas.v8.settings',[
            'settings'=>DB::table('enfas_v8_settings')->pluck('value','key')
        ]);
    }

    public function saveSettings(Request $r,V8Support $s)
    {
        $d=$r->validate(['company_name'=>['nullable','string','max:255'],'default_reminder_minutes'=>['nullable','integer'],'appointment_interval'=>['nullable','integer'],'whatsapp_footer'=>['nullable','string','max:255']]);
        foreach($d as $k=>$v)DB::table('enfas_v8_settings')->updateOrInsert(
            ['key'=>$k],
            [
                'group'=>'general',
                'value'=>$v,
                'type'=>is_numeric($v)?'number':'string',
                'updated_at'=>now(),
                'created_at'=>now(),
            ]
        );
        $s->audit('settings','update',null,'Configurações atualizadas');
        return back()->with('success','Configurações salvas.');
    }

    private function dateColumn(): ?string
    {
        foreach(['starts_at','scheduled_at','start_at','appointment_at','date'] as $c)if(Schema::hasColumn('appointments',$c))return $c;
        return null;
    }
}
