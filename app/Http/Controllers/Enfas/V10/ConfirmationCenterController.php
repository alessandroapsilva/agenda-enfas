<?php

namespace App\Http\Controllers\Enfas\V10;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConfirmationCenterController extends Controller
{
    public function index(Request $request)
    {
        $dateColumn=$this->dateColumn();

        $query=$this->appointmentQuery();

        if ($dateColumn) {
            $query->orderBy('appointments.'.$dateColumn);
        } else {
            $query->orderByDesc('appointments.id');
        }

        $filter=$request->string('status')->toString() ?: 'pending';

        $this->applyStatusFilter($query,$filter);

        if ($request->filled('q')) {
            $q='%'.$request->string('q')->trim().'%';

            $query->where(function($sub) use($q) {
                $sub->where('patients.name','like',$q)
                    ->orWhere('patients.phone','like',$q)
                    ->orWhere('professionals.name','like',$q)
                    ->orWhere('services.name','like',$q);
            });
        }

        if ($dateColumn && $request->filled('date')) {
            $query->whereDate(
                'appointments.'.$dateColumn,
                $request->date('date')
            );
        } elseif ($dateColumn && $filter==='pending') {
            $query->whereDate(
                'appointments.'.$dateColumn,
                '>=',
                today()
            );
        }

        $rows=$query->paginate(30)->withQueryString();

        return view('enfas.v10.confirmations',[
            'rows'=>$rows,
            'filter'=>$filter,
            'metrics'=>$this->metrics($dateColumn),
            'dateColumn'=>$dateColumn,
        ]);
    }

    public function history(Request $request)
    {
        if (! Schema::hasTable('wa_messages')) {
            return view('enfas.v10.communication-history',[
                'rows'=>collect(),
                'available'=>false,
            ]);
        }

        $columns=Schema::getColumnListing('wa_messages');

        $statusColumn=$this->firstColumn($columns,[
            'status','delivery_status'
        ]);

        $directionColumn=$this->firstColumn($columns,[
            'direction','message_direction'
        ]);

        $bodyColumn=$this->firstColumn($columns,[
            'body','content','message','text'
        ]);

        $phoneColumn=$this->firstColumn($columns,[
            'phone','to_phone','recipient_phone'
        ]);

        $query=DB::table('wa_messages')->orderByDesc('id');

        if ($request->filled('q')) {
            $q='%'.$request->string('q')->trim().'%';

            $query->where(function($sub) use(
                $q,
                $bodyColumn,
                $phoneColumn,
                $statusColumn
            ) {
                if ($bodyColumn) {
                    $sub->orWhere($bodyColumn,'like',$q);
                }

                if ($phoneColumn) {
                    $sub->orWhere($phoneColumn,'like',$q);
                }

                if ($statusColumn) {
                    $sub->orWhere($statusColumn,'like',$q);
                }
            });
        }

        return view('enfas.v10.communication-history',[
            'rows'=>$query->paginate(50)->withQueryString(),
            'available'=>true,
            'statusColumn'=>$statusColumn,
            'directionColumn'=>$directionColumn,
            'bodyColumn'=>$bodyColumn,
            'phoneColumn'=>$phoneColumn,
        ]);
    }

    public function mark(Request $request,int $appointment)
    {
        $action=$request->validate([
            'action'=>[
                'required',
                'in:pending,confirmed,cancelled,attended,no_show',
            ],
        ])['action'];

        abort_unless(
            DB::table('appointments')
                ->where('id',$appointment)
                ->exists(),
            404
        );

        $payload=[];

        foreach([
            'confirmed_at',
            'cancelled_at',
            'attended_at',
            'no_show_at',
        ] as $column) {
            if (Schema::hasColumn('appointments',$column)) {
                $payload[$column]=null;
            }
        }

        $timestamp=match($action) {
            'confirmed'=>'confirmed_at',
            'cancelled'=>'cancelled_at',
            'attended'=>'attended_at',
            'no_show'=>'no_show_at',
            default=>null,
        };

        if ($timestamp
            && Schema::hasColumn('appointments',$timestamp)) {
            $payload[$timestamp]=now();
        }

        if (Schema::hasColumn('appointments','status')) {
            $payload['status']=$action;
        }

        if (Schema::hasColumn('appointments','updated_at')) {
            $payload['updated_at']=now();
        }

        DB::table('appointments')
            ->where('id',$appointment)
            ->update($payload);

        if (Schema::hasTable('audit_logs')) {
            DB::table('audit_logs')->insert([
                'user_id'=>auth()->id(),
                'module'=>'confirmations',
                'action'=>'status_'.$action,
                'entity_type'=>'appointment',
                'entity_id'=>$appointment,
                'description'=>'Status de confirmação alterado pela Central',
                'ip'=>$request->ip(),
                'user_agent'=>$request->userAgent(),
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        }

        return back()->with(
            'success',
            'Status atualizado.'
        );
    }

    private function appointmentQuery()
    {
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
            );

        $hasLocation=Schema::hasTable('locations')
            && Schema::hasColumn('appointments','location_id');

        if ($hasLocation) {
            $query->leftJoin(
                'locations',
                'locations.id',
                '=',
                'appointments.location_id'
            );
        }

        $select=[
            'appointments.*',
            'patients.name as patient_name',
            'patients.phone as patient_phone',
            'professionals.name as professional_name',
            'services.name as service_name',
        ];

        if ($hasLocation) {
            $select[]='locations.name as location_name';
        }

        return $query->select($select);
    }

    private function metrics(?string $dateColumn): array
    {
        $base=DB::table('appointments');

        if ($dateColumn) {
            $base->whereDate(
                $dateColumn,
                '>=',
                today()
            );
        }

        return [
            'pending'=>$this->countStatus(clone $base,'pending'),
            'confirmed'=>$this->countStatus(clone $base,'confirmed'),
            'cancelled'=>$this->countStatus(clone $base,'cancelled'),
            'attended'=>$this->countStatus(clone $base,'attended'),
            'no_show'=>$this->countStatus(clone $base,'no_show'),
            'today'=>$dateColumn
                ?DB::table('appointments')
                    ->whereDate($dateColumn,today())
                    ->count()
                :0,
        ];
    }

    private function countStatus($query,string $status): int
    {
        $this->applyStatusFilter($query,$status);

        return $query->count();
    }

    private function applyStatusFilter($query,string $status): void
    {
        $timestampMap=[
            'confirmed'=>'confirmed_at',
            'cancelled'=>'cancelled_at',
            'attended'=>'attended_at',
            'no_show'=>'no_show_at',
        ];

        if ($status==='pending') {
            foreach($timestampMap as $column) {
                if (Schema::hasColumn('appointments',$column)) {
                    $query->whereNull($column);
                }
            }

            return;
        }

        $column=$timestampMap[$status]??null;

        if ($column
            && Schema::hasColumn('appointments',$column)) {
            $query->whereNotNull($column);

            return;
        }

        if (Schema::hasColumn('appointments','status')) {
            $query->where('status',$status);
        }
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

    private function firstColumn(
        array $columns,
        array $candidates
    ): ?string {
        foreach($candidates as $candidate) {
            if (in_array($candidate,$columns,true)) {
                return $candidate;
            }
        }

        return null;
    }
}
