<?php

namespace App\Http\Controllers\Enfas\V92;

use App\Http\Controllers\Controller;
use App\Services\Enfas\V92\ProductionHealthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WorkspaceController extends Controller
{
    public function index(ProductionHealthService $health)
    {
        // V11_ENTRY_REDIRECT
        return redirect()->route('v11.today');

        $dateColumn=$this->dateColumn();

        $metrics=[
            'today'=>0,
            'pending'=>0,
            'confirmed'=>0,
            'cancelled'=>0,
            'messages'=>0,
            'professionals'=>$this->activeCount('professionals'),
        ];

        if ($dateColumn) {
            $metrics['today']=DB::table('appointments')
                ->whereDate($dateColumn,today())
                ->count();

            $future=DB::table('appointments')
                ->whereDate($dateColumn,'>=',today());

            $metrics['pending']=$this->statusCount(
                clone $future,
                'pending'
            );

            $metrics['confirmed']=$this->statusCount(
                clone $future,
                'confirmed'
            );

            $metrics['cancelled']=$this->statusCount(
                clone $future,
                'cancelled'
            );
        }

        if (Schema::hasTable('wa_messages')
            && Schema::hasColumn('wa_messages','created_at')) {
            $metrics['messages']=DB::table('wa_messages')
                ->whereDate('created_at',today())
                ->count();
        }

        $upcoming=$this->upcoming($dateColumn);
        $healthSummary=$health->summary(false);

        return view(
            'enfas.v10.dashboard',
            compact(
                'metrics',
                'upcoming',
                'healthSummary',
                'dateColumn'
            )
        );
    }

    private function upcoming(?string $dateColumn)
    {
        if (! $dateColumn) {
            return collect();
        }

        $query=DB::table('appointments')
            ->join(
                'patients',
                'patients.id',
                '=',
                'appointments.patient_id'
            )
            ->join(
                'professionals',
                'professionals.id',
                '=',
                'appointments.professional_id'
            )
            ->join(
                'services',
                'services.id',
                '=',
                'appointments.service_id'
            )
            ->whereDate(
                'appointments.'.$dateColumn,
                '>=',
                today()
            )
            ->orderBy('appointments.'.$dateColumn)
            ->limit(8)
            ->select([
                'appointments.*',
                'patients.name as patient_name',
                'patients.phone as patient_phone',
                'professionals.name as professional_name',
                'services.name as service_name',
            ]);

        return $query->get();
    }

    private function statusCount($query,string $status): int
    {
        $map=[
            'confirmed'=>'confirmed_at',
            'cancelled'=>'cancelled_at',
        ];

        if ($status==='pending') {
            foreach([
                'confirmed_at',
                'cancelled_at',
                'attended_at',
                'no_show_at',
            ] as $column) {
                if (Schema::hasColumn('appointments',$column)) {
                    $query->whereNull($column);
                }
            }

            return $query->count();
        }

        $column=$map[$status]??null;

        if ($column
            && Schema::hasColumn('appointments',$column)) {
            return $query->whereNotNull($column)->count();
        }

        if (Schema::hasColumn('appointments','status')) {
            return $query->where('status',$status)->count();
        }

        return 0;
    }

    private function activeCount(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query=DB::table($table);

        if (Schema::hasColumn($table,'is_active')) {
            $query->where('is_active',true);
        } elseif (Schema::hasColumn($table,'active')) {
            $query->where('active',true);
        }

        return $query->count();
    }

    private function dateColumn(): ?string
    {
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
}
