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
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $rangeStart = $from->copy()->startOfDay();
        $rangeEnd = $to->copy()->endOfDay();

        $locations = Schema::hasTable('locations')
            ? DB::table('locations')
                ->where('is_active', true)
                ->orderByDesc('is_main')
                ->orderBy('name')
                ->get(['id','name','code'])
            : collect();

        $locationId = $request->integer('location_id') ?: null;

        if ($locationId && ! $locations->contains('id', $locationId)) {
            $locationId = null;
        }

        $base = DB::table('appointments')
            ->whereBetween('start_at', [$rangeStart, $rangeEnd]);

        if (
            $locationId
            && Schema::hasColumn('appointments', 'location_id')
        ) {
            $base->where('location_id', $locationId);
        }

        $metrics = [
            'appointments' => (clone $base)->count(),
            'confirmed' => (clone $base)->where('status', 'confirmed')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
            'no_show' => (clone $base)->where('status', 'no_show')->count(),
            'awaiting' => (clone $base)->whereIn('status', ['scheduled','awaiting_confirmation'])->count(),
        ];

        $denominator = max(1, $metrics['appointments']);

        $confirmedEver = Schema::hasColumn('appointments', 'confirmed_at')
            ? (clone $base)->whereNotNull('confirmed_at')->count()
            : $metrics['confirmed'];

        $responded = Schema::hasColumn('appointments', 'confirmation_status')
            ? (clone $base)
                ->whereIn('confirmation_status', ['confirmed','cancelled'])
                ->count()
            : ($metrics['confirmed'] + $metrics['cancelled']);

        $presenceOutcomes = $metrics['completed'] + $metrics['no_show'];

        $rates = [
            'response' => round(($responded / $denominator) * 100, 1),
            'confirmation' => round(($confirmedEver / $denominator) * 100, 1),
            'presence' => $presenceOutcomes > 0
                ? round(($metrics['completed'] / $presenceOutcomes) * 100, 1)
                : 0.0,
            'completion' => round(($metrics['completed'] / $denominator) * 100, 1),
            'cancellation' => round(($metrics['cancelled'] / $denominator) * 100, 1),
            'no_show' => round(($metrics['no_show'] / $denominator) * 100, 1),
        ];

        $professionalsQuery = DB::table('appointments as a')
            ->join('professionals as p', 'p.id', '=', 'a.professional_id')
            ->whereBetween('a.start_at', [$rangeStart, $rangeEnd]);

        if (
            $locationId
            && Schema::hasColumn('appointments', 'location_id')
        ) {
            $professionalsQuery->where('a.location_id', $locationId);
        }

        $professionals = $professionalsQuery
            ->groupBy('p.id', 'p.name')
            ->select([
                'p.id',
                'p.name',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN a.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed"),
                DB::raw("SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN a.status = 'no_show' THEN 1 ELSE 0 END) as no_show"),
                DB::raw('COALESCE(SUM(a.duration_minutes),0) as scheduled_minutes'),
            ])
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $total = max(1, (int) $row->total);
                $row->confirmation_rate = round(((int) $row->confirmed / $total) * 100, 1);
                $row->no_show_rate = round(((int) $row->no_show / $total) * 100, 1);

                return $row;
            });

        $servicesQuery = DB::table('appointments as a')
            ->join('services as s', 's.id', '=', 'a.service_id')
            ->whereBetween('a.start_at', [$rangeStart, $rangeEnd]);

        if (
            $locationId
            && Schema::hasColumn('appointments', 'location_id')
        ) {
            $servicesQuery->where('a.location_id', $locationId);
        }

        $services = $servicesQuery
            ->groupBy('s.id', 's.name')
            ->select([
                's.id',
                's.name',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN a.status = 'no_show' THEN 1 ELSE 0 END) as no_show"),
            ])
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        $dailyQuery = DB::table('appointments')
            ->whereBetween('start_at', [$rangeStart, $rangeEnd]);

        if (
            $locationId
            && Schema::hasColumn('appointments', 'location_id')
        ) {
            $dailyQuery->where('location_id', $locationId);
        }

        $daily = $dailyQuery
            ->selectRaw('DATE(start_at) as day')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled")
            ->selectRaw("SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show")
            ->groupByRaw('DATE(start_at)')
            ->orderBy('day')
            ->get();

        $communication = [
            'sent' => 0,
            'delivered' => 0,
            'read' => 0,
            'failed' => 0,
            'received' => 0,
        ];

        if (Schema::hasTable('wa_messages')) {
            $waBase = DB::table('wa_messages')
                ->whereBetween('created_at', [$rangeStart, $rangeEnd]);

            foreach (array_keys($communication) as $status) {
                $communication[$status] = (clone $waBase)
                    ->where('status', $status)
                    ->count();
            }
        }

        $waitlist = [
            'waiting' => 0,
            'offered' => 0,
            'accepted' => 0,
        ];

        if (Schema::hasTable('waitlist_entries')) {
            foreach (array_keys($waitlist) as $status) {
                $waitlist[$status] = DB::table('waitlist_entries')
                    ->where('status', $status)
                    ->count();
            }
        }

        $journey = [
            'confirmed' => $confirmedEver,
            'checkins' => 0,
            'completed' => $metrics['completed'],
            'responses' => 0,
            'average_score' => null,
            'average_stars' => null,
            'nps' => null,
        ];

        if (Schema::hasColumn('appointments', 'check_in_completed_at')) {
            $journey['checkins'] = (clone $base)
                ->whereNotNull('check_in_completed_at')
                ->count();
        }

        if (Schema::hasColumn('appointments', 'satisfaction_score')) {
            $scores = (clone $base)
                ->whereNotNull('satisfaction_score')
                ->pluck('satisfaction_score')
                ->map(fn ($score) => (int) $score);

            $journey['responses'] = $scores->count();

            if ($scores->isNotEmpty()) {
                $journey['average_score'] = round($scores->avg(), 1);
                $promoters = $scores->filter(fn ($score) => $score >= 9)->count();
                $detractors = $scores->filter(fn ($score) => $score <= 6)->count();

                $journey['nps'] = (int) round(
                    (($promoters / $scores->count()) * 100)
                    - (($detractors / $scores->count()) * 100)
                );
            }
        }

        if (Schema::hasColumn('appointments', 'satisfaction_stars')) {
            $stars = (clone $base)
                ->whereNotNull('satisfaction_stars')
                ->pluck('satisfaction_stars')
                ->map(fn ($score) => (int) $score);

            if ($stars->isNotEmpty()) {
                $journey['average_stars'] = round($stars->avg(), 1);
            }
        }

        $cancellationReasons = collect();

        if (Schema::hasColumn('appointments', 'cancellation_reason')) {
            $cancellationReasons = (clone $base)
                ->where('status', 'cancelled')
                ->whereNotNull('cancellation_reason')
                ->where('cancellation_reason', '<>', '')
                ->select('cancellation_reason', DB::raw('COUNT(*) as total'))
                ->groupBy('cancellation_reason')
                ->orderByDesc('total')
                ->limit(8)
                ->get();
        }

        return view('enfas.v92.reports', compact(
            'metrics',
            'rates',
            'professionals',
            'services',
            'daily',
            'communication',
            'waitlist',
            'journey',
            'cancellationReasons',
            'from',
            'to',
            'locations',
            'locationId'
        ));
    }

    public function exportReports(Request $request)
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $locationId = $request->integer('location_id') ?: null;

        $rowsQuery = DB::table('appointments as a')
            ->leftJoin('patients as p', 'p.id', '=', 'a.patient_id')
            ->leftJoin('professionals as pro', 'pro.id', '=', 'a.professional_id')
            ->leftJoin('services as s', 's.id', '=', 'a.service_id')
            ->whereBetween('a.start_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ]);

        if (
            $locationId
            && Schema::hasColumn('appointments', 'location_id')
        ) {
            $rowsQuery->where('a.location_id', $locationId);
        }

        $rows = $rowsQuery
            ->orderBy('a.start_at')
            ->get([
                'a.code',
                'a.start_at',
                'a.end_at',
                'a.status',
                'a.confirmation_status',
                'a.check_in_completed_at',
                'a.satisfaction_score',
                'a.satisfaction_stars',
                'a.satisfaction_comment',
                'p.name as patient_name',
                'pro.name as professional_name',
                's.name as service_name',
            ]);

        $filename = 'agenda-enfas-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Código',
                'Data',
                'Início',
                'Fim',
                'Paciente',
                'Profissional',
                'Serviço',
                'Status',
                'Confirmação',
                'Check-in',
                'NPS',
                'Estrelas',
                'Comentário',
            ], ';');

            foreach ($rows as $row) {
                $start = Carbon::parse($row->start_at);
                $end = Carbon::parse($row->end_at);

                fputcsv($out, [
                    $row->code,
                    $start->format('d/m/Y'),
                    $start->format('H:i'),
                    $end->format('H:i'),
                    $row->patient_name,
                    $row->professional_name,
                    $row->service_name,
                    $row->status,
                    $row->confirmation_status,
                    $row->check_in_completed_at ? 'Sim' : 'Não',
                    $row->satisfaction_score,
                    $row->satisfaction_stars,
                    $row->satisfaction_comment,
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
            ->orderByRaw("
                CASE severity
                    WHEN 'danger' THEN 1
                    WHEN 'warning' THEN 2
                    WHEN 'info' THEN 3
                    ELSE 4
                END
            ")
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
