<?php

namespace App\Http\Controllers\Enfas;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleController extends Controller
{
    private function rows(string $table,int $limit=50)
    {
        return Schema::hasTable($table)
            ? DB::table($table)->orderByDesc('id')->limit($limit)->get()
            : collect();
    }

    public function patients()
    {
        return view('enfas.core.patients',[
            'rows'=>Schema::hasTable('patients') ? DB::table('patients')->orderBy('name')->paginate(40) : collect(),
            'ready'=>Schema::hasTable('patients'),
        ]);
    }

    public function professionals()
    {
        return view('enfas.core.professionals',[
            'rows'=>Schema::hasTable('professionals') ? DB::table('professionals')->orderBy('name')->get() : collect(),
            'ready'=>Schema::hasTable('professionals'),
        ]);
    }

    public function services()
    {
        return view('enfas.core.services',[
            'rows'=>Schema::hasTable('services') ? DB::table('services')->orderBy('name')->get() : collect(),
            'ready'=>Schema::hasTable('services'),
        ]);
    }

    public function appointments()
    {
        return view('enfas.core.appointments',[
            'rows'=>Schema::hasTable('appointments') ? DB::table('appointments')->orderByDesc('start_at')->limit(100)->get() : collect(),
            'ready'=>Schema::hasTable('appointments'),
        ]);
    }

    public function locations()
    {
        return view('enfas.core.simple-table',[
            'title'=>'Locais','subtitle'=>'Unidades e pontos de atendimento cadastrados.','icon'=>'bi-geo-alt',
            'ready'=>Schema::hasTable('locations'),'rows'=>$this->rows('locations'),
            'columns'=>['name'=>'Nome','code'=>'Código','phone'=>'Telefone','address'=>'Endereço']
        ]);
    }

    public function availability()
    {
        return view('enfas.core.availability',[
            'schedules'=>Schema::hasTable('professional_schedules'),
            'blocks'=>Schema::hasTable('schedule_blocks'),
        ]);
    }

    public function customFields()
    {
        return view('enfas.core.simple-table',[
            'title'=>'Campos personalizados','subtitle'=>'Campos configuráveis usados no cadastro e no agendamento.','icon'=>'bi-ui-checks-grid',
            'ready'=>Schema::hasTable('custom_fields'),'rows'=>$this->rows('custom_fields'),
            'columns'=>['name'=>'Campo','entity_type'=>'Entidade','field_type'=>'Tipo','is_required'=>'Obrigatório','is_active'=>'Ativo']
        ]);
    }

    public function reports()
    {
        $cards = [
            'Agendamentos'=>Schema::hasTable('appointments') ? DB::table('appointments')->count() : 0,
            'Pacientes'=>Schema::hasTable('patients') ? DB::table('patients')->count() : 0,
            'Profissionais'=>Schema::hasTable('professionals') ? DB::table('professionals')->count() : 0,
            'Serviços'=>Schema::hasTable('services') ? DB::table('services')->count() : 0,
        ];

        return view('enfas.reports',compact('cards'));
    }

    public function alerts()
    {
        return view('enfas.core.simple-table',[
            'title'=>'Alertas','subtitle'=>'Ocorrências que exigem atenção da equipe.','icon'=>'bi-bell',
            'ready'=>Schema::hasTable('system_alerts'),'rows'=>$this->rows('system_alerts'),
            'columns'=>['title'=>'Título','severity'=>'Severidade','message'=>'Mensagem','is_read'=>'Lido','created_at'=>'Data']
        ]);
    }

    public function audit()
    {
        return view('enfas.core.simple-table',[
            'title'=>'Auditoria','subtitle'=>'Registro das ações realizadas dentro do sistema.','icon'=>'bi-shield-check',
            'ready'=>Schema::hasTable('audit_logs'),'rows'=>$this->rows('audit_logs',100),
            'columns'=>['action'=>'Ação','entity_type'=>'Entidade','entity_id'=>'Registro','description'=>'Descrição','created_at'=>'Data']
        ]);
    }
}
