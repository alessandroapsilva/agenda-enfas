<?php

namespace App\Http\Controllers\Enfas\V92;

use App\Http\Controllers\Controller;
use App\Services\Enfas\V92\ProductionHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ManagementController extends Controller
{
    public function availability()
    {
        return view('enfas.v92.availability',[
            'professionals'=>DB::table('professionals')
                ->orderBy('name')->get(),
            'locations'=>Schema::hasTable('locations')
                ?DB::table('locations')->where('is_active',true)->orderBy('name')->get()
                :collect(),
            'rules'=>DB::table('professional_availabilities as a')
                ->join('professionals as p','p.id','=','a.professional_id')
                ->leftJoin('locations as l','l.id','=','a.location_id')
                ->select('a.*','p.name as professional_name','l.name as location_name')
                ->orderBy('p.name')
                ->orderBy('a.weekday')
                ->orderBy('a.starts_at')
                ->get(),
            'absences'=>DB::table('professional_absences as a')
                ->join('professionals as p','p.id','=','a.professional_id')
                ->select('a.*','p.name as professional_name')
                ->orderByDesc('a.starts_at')
                ->limit(100)
                ->get(),
        ]);
    }

    public function storeAvailability(Request $request)
    {
        $data=$request->validate([
            'professional_id'=>['required','integer','exists:professionals,id'],
            'location_id'=>['nullable','integer'],
            'weekday'=>['required','integer','between:0,6'],
            'starts_at'=>['required','date_format:H:i'],
            'ends_at'=>['required','date_format:H:i','after:starts_at'],
            'slot_minutes'=>['required','integer','between:5,240'],
        ]);

        DB::table('professional_availabilities')->insert(
            $data+[
                'is_active'=>true,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]
        );

        $this->logAudit('availability','create','Disponibilidade cadastrada');

        return back()->with('success','Disponibilidade cadastrada.');
    }

    public function deleteAvailability(int $id)
    {
        DB::table('professional_availabilities')->where('id',$id)->delete();
        $this->logAudit('availability','delete','Disponibilidade removida');

        return back()->with('success','Disponibilidade removida.');
    }

    public function storeAbsence(Request $request)
    {
        $data=$request->validate([
            'professional_id'=>['required','integer','exists:professionals,id'],
            'starts_at'=>['required','date'],
            'ends_at'=>['required','date','after:starts_at'],
            'reason'=>['nullable','string','max:255'],
        ]);

        DB::table('professional_absences')->insert(
            $data+[
                'is_active'=>true,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]
        );

        $this->logAudit('availability','absence_create','Ausência cadastrada');

        return back()->with('success','Ausência/bloqueio cadastrado.');
    }

    public function deleteAbsence(int $id)
    {
        DB::table('professional_absences')->where('id',$id)->delete();
        $this->logAudit('availability','absence_delete','Ausência removida');

        return back()->with('success','Ausência removida.');
    }

    public function reports(Request $request)
    {
        $from=$request->date('from')??now()->startOfMonth();
        $to=$request->date('to')??now()->endOfMonth();
        $dateColumn=$this->appointmentDateColumn();

        $metrics=[
            'appointments'=>0,
            'confirmed'=>0,
            'cancelled'=>0,
            'attended'=>0,
            'no_show'=>0,
        ];

        $topProfessionals=collect();
        $topServices=collect();

        if ($dateColumn) {
            $base=DB::table('appointments')
                ->whereBetween(
                    $dateColumn,
                    [$from->copy()->startOfDay(),$to->copy()->endOfDay()]
                );

            $metrics['appointments']=(clone $base)->count();

            foreach([
                'confirmed_at'=>'confirmed',
                'cancelled_at'=>'cancelled',
                'attended_at'=>'attended',
                'no_show_at'=>'no_show',
            ] as $column=>$key) {
                if (Schema::hasColumn('appointments',$column)) {
                    $metrics[$key]=(clone $base)
                        ->whereNotNull($column)
                        ->count();
                }
            }

            $topProfessionals=DB::table('appointments as a')
                ->join('professionals as p','p.id','=','a.professional_id')
                ->whereBetween(
                    'a.'.$dateColumn,
                    [$from->copy()->startOfDay(),$to->copy()->endOfDay()]
                )
                ->groupBy('p.id','p.name')
                ->select('p.name',DB::raw('COUNT(*) total'))
                ->orderByDesc('total')
                ->limit(8)
                ->get();

            $topServices=DB::table('appointments as a')
                ->join('services as s','s.id','=','a.service_id')
                ->whereBetween(
                    'a.'.$dateColumn,
                    [$from->copy()->startOfDay(),$to->copy()->endOfDay()]
                )
                ->groupBy('s.id','s.name')
                ->select('s.name',DB::raw('COUNT(*) total'))
                ->orderByDesc('total')
                ->limit(8)
                ->get();
        }

        return view(
            'enfas.v92.reports',
            compact(
                'metrics',
                'topProfessionals',
                'topServices',
                'from',
                'to'
            )
        );
    }

    public function audit(Request $request)
    {
        $query=DB::table('audit_logs')->orderByDesc('id');

        if ($request->filled('q')) {
            $q='%'.$request->string('q')->trim().'%';

            $query->where(function($sub) use($q) {
                $sub->where('module','like',$q)
                    ->orWhere('action','like',$q)
                    ->orWhere('description','like',$q);
            });
        }

        return view('enfas.v92.audit',[
            'rows'=>$query->paginate(50)->withQueryString(),
        ]);
    }

    public function alerts(ProductionHealthService $health)
    {
        $alerts=[];

        if (Schema::hasTable('wa_templates')) {
            $rejected=DB::table('wa_templates')
                ->where('status','REJECTED')
                ->where(function($q){
                    $q->whereNull('archived_at');
                })
                ->count();

            if ($rejected>0) {
                $alerts[]=[
                    'severity'=>'danger',
                    'title'=>'Modelos WhatsApp rejeitados',
                    'message'=>$rejected.' modelo(s) precisam de revisão.',
                    'url'=>'/whatsapp/templates',
                ];
            }

            $pending=DB::table('wa_templates')
                ->where('status','PENDING')
                ->count();

            if ($pending>0) {
                $alerts[]=[
                    'severity'=>'warning',
                    'title'=>'Modelos aguardando Meta',
                    'message'=>$pending.' modelo(s) estão em análise.',
                    'url'=>'/whatsapp/templates',
                ];
            }
        }

        $summary=$health->summary(false);

        foreach($summary['checks'] as $check) {
            if (! $check['ok']) {
                $alerts[]=[
                    'severity'=>'danger',
                    'title'=>'Saúde do sistema',
                    'message'=>$check['label']
                        .($check['detail']?' — '.$check['detail']:''),
                    'url'=>'/sistema/saude',
                ];
            }
        }

        $manual=DB::table('operational_alerts')
            ->where('status','open')
            ->orderByRaw("FIELD(severity,'danger','warning','info')")
            ->orderByDesc('id')
            ->get();

        return view(
            'enfas.v92.alerts',
            compact('alerts','manual')
        );
    }

    public function resolveAlert(int $id)
    {
        DB::table('operational_alerts')
            ->where('id',$id)
            ->update([
                'status'=>'resolved',
                'resolved_at'=>now(),
                'resolved_by'=>auth()->id(),
                'updated_at'=>now(),
            ]);

        return back()->with('success','Alerta resolvido.');
    }

    public function settings()
    {
        return view('enfas.v92.settings',[
            'settings'=>DB::table('enfas_settings')->pluck('value','key'),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data=$request->validate([
            'company_name'=>['nullable','string','max:255'],
            'default_reminder_minutes'=>['nullable','integer','between:5,10080'],
            'appointment_interval'=>['nullable','integer','between:5,240'],
            'business_hours_start'=>['nullable','date_format:H:i'],
            'business_hours_end'=>['nullable','date_format:H:i'],
            'whatsapp_footer'=>['nullable','string','max:255'],
        ]);

        foreach($data as $key=>$value) {
            DB::table('enfas_settings')->updateOrInsert(
                ['key'=>$key],
                [
                    'group'=>'general',
                    'value'=>$value,
                    'type'=>is_numeric($value)?'number':'string',
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]
            );
        }

        $this->logAudit('settings','update','Configurações operacionais atualizadas');

        return back()->with('success','Configurações salvas.');
    }

    public function health(ProductionHealthService $health)
    {
        return view('enfas.v92.health',[
            'summary'=>$health->summary(true),
        ]);
    }

    public function healthz(ProductionHealthService $health)
    {
        $summary=$health->summary(false);

        return response()->json([
            'status'=>$summary['ok']?'ok':'degraded',
            'time'=>now()->toIso8601String(),
        ],$summary['ok']?200:503);
    }

    private function appointmentDateColumn(): ?string
    {
        if (! Schema::hasTable('appointments')) {
            return null;
        }

        foreach([
            'starts_at',
            'scheduled_at',
            'start_at',
            'appointment_at',
            'date',
        ] as $column) {
            if (Schema::hasColumn('appointments',$column)) {
                return $column;
            }
        }

        return null;
    }

    private function logAudit(
        string $module,
        string $action,
        string $description
    ): void {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')->insert([
            'user_id'=>auth()->id(),
            'module'=>$module,
            'action'=>$action,
            'description'=>$description,
            'ip'=>request()->ip(),
            'user_agent'=>request()->userAgent(),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
    }
}
