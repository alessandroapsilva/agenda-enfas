<?php

namespace App\Http\Controllers\Enfas\V9;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MasterDataController extends Controller
{
    private array $entities = [
        'professionals' => [
            'title' => 'Profissionais',
            'singular' => 'Profissional',
            'path' => '/profissionais',
            'reference' => ['appointments','professional_id'],
            'fields' => [
                ['name','Nome completo','text',true],
                ['email','E-mail','email',false],
                ['phone','Telefone / WhatsApp','text',false],
                ['specialty','Especialidade','text',false],
                ['registration_type','Conselho','text',false],
                ['registration_number','Número do registro','text',false],
                ['notes','Observações','textarea',false],
            ],
        ],
        'patients' => [
            'title' => 'Pacientes',
            'singular' => 'Paciente',
            'path' => '/pacientes',
            'reference' => ['appointments','patient_id'],
            'fields' => [
                ['name','Nome completo','text',true],
                ['phone','Telefone / WhatsApp','text',true],
                ['email','E-mail','email',false],
                ['cpf','CPF','text',false],
                ['birth_date','Data de nascimento','date',false],
                ['notes','Observações administrativas','textarea',false],
            ],
        ],
        'services' => [
            'title' => 'Serviços',
            'singular' => 'Serviço',
            'path' => '/servicos',
            'reference' => ['appointments','service_id'],
            'fields' => [
                ['name','Nome do serviço','text',true],
                ['duration_minutes','Duração em minutos','number',true],
                ['price','Valor','number',false],
                ['description','Descrição','textarea',false],
                ['preparation','Orientações / preparo','textarea',false],
            ],
        ],
        'locations' => [
            'title' => 'Unidades e locais',
            'singular' => 'Unidade',
            'path' => '/locais',
            'reference' => ['appointments','location_id'],
            'fields' => [
                ['name','Nome da unidade','text',true],
                ['phone','Telefone','text',false],
                ['email','E-mail','email',false],
                ['address','Endereço','text',false],
                ['city','Cidade','text',false],
                ['state','UF','text',false],
                ['postal_code','CEP','text',false],
                ['notes','Observações','textarea',false],
            ],
        ],
    ];

    public function index(Request $request,string $entity)
    {
        $config=$this->config($entity);

        $query=DB::table($entity)->orderBy('name');

        if ($request->filled('q')) {
            $q='%'.$request->string('q')->trim().'%';
            $query->where('name','like',$q);
        }

        $rows=$query->paginate(25)->withQueryString();
        $editing=$request->integer('edit')
            ? DB::table($entity)->find($request->integer('edit'))
            : null;

        return view('enfas.v9.master',compact(
            'entity','config','rows','editing'
        ));
    }

    public function store(Request $request,string $entity)
    {
        $config=$this->config($entity);
        $payload=$this->payload($request,$entity,$config);

        if (Schema::hasColumn($entity,'is_active')) {
            $payload['is_active']=true;
        }

        if (Schema::hasColumn($entity,'active')) {
            $payload['active']=true;
        }

        if (Schema::hasColumn($entity,'created_at')) {
            $payload['created_at']=now();
        }

        if (Schema::hasColumn($entity,'updated_at')) {
            $payload['updated_at']=now();
        }

        DB::table($entity)->insert($payload);

        return redirect($config['path'])
            ->with('success',$config['singular'].' cadastrado com sucesso.');
    }

    public function update(Request $request,string $entity,int $id)
    {
        $config=$this->config($entity);
        abort_unless(DB::table($entity)->where('id',$id)->exists(),404);

        $payload=$this->payload($request,$entity,$config);

        if (Schema::hasColumn($entity,'updated_at')) {
            $payload['updated_at']=now();
        }

        DB::table($entity)->where('id',$id)->update($payload);

        return redirect($config['path'])
            ->with('success',$config['singular'].' atualizado com sucesso.');
    }

    public function toggle(string $entity,int $id)
    {
        $config=$this->config($entity);
        $row=DB::table($entity)->find($id);
        abort_unless($row,404);

        $current=(bool)($row->is_active??$row->active??true);
        $payload=[];

        if (Schema::hasColumn($entity,'is_active')) {
            $payload['is_active']=!$current;
        }

        if (Schema::hasColumn($entity,'active')) {
            $payload['active']=!$current;
        }

        if (Schema::hasColumn($entity,'updated_at')) {
            $payload['updated_at']=now();
        }

        DB::table($entity)->where('id',$id)->update($payload);

        return back()->with(
            'success',
            $config['singular'].($current?' desativado.':' ativado.')
        );
    }

    public function destroy(string $entity,int $id)
    {
        $config=$this->config($entity);
        $row=DB::table($entity)->find($id);
        abort_unless($row,404);

        [$refTable,$refColumn]=$config['reference'];

        $used=false;

        if (Schema::hasTable($refTable)
            && Schema::hasColumn($refTable,$refColumn)) {
            $used=DB::table($refTable)
                ->where($refColumn,$id)
                ->exists();
        }

        if ($used) {
            return back()->withErrors([
                'delete' =>
                    $config['singular']
                    .' possui histórico de agendamentos e não pode ser apagado. '
                    .'Use Desativar para preservar a integridade do sistema.',
            ]);
        }

        DB::table($entity)->where('id',$id)->delete();

        return back()->with(
            'success',
            $config['singular'].' excluído definitivamente.'
        );
    }

    private function payload(
        Request $request,
        string $entity,
        array $config
    ): array {
        $rules=[];

        foreach ($config['fields'] as [$name,$label,$type,$required]) {
            if (! Schema::hasColumn($entity,$name)) {
                continue;
            }

            $fieldRules=$required?['required']:['nullable'];

            $fieldRules[] = match($type) {
                'email' => 'email',
                'number' => 'numeric',
                'date' => 'date',
                default => 'string',
            };

            $fieldRules[]='max:4000';
            $rules[$name]=$fieldRules;
        }

        $data=$request->validate($rules);
        $columns=array_flip(Schema::getColumnListing($entity));

        return array_filter(
            $data,
            fn($value,$key)=>isset($columns[$key]),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function config(string $entity): array
    {
        abort_unless(isset($this->entities[$entity]),404);
        abort_unless(Schema::hasTable($entity),404);

        return $this->entities[$entity];
    }
}
