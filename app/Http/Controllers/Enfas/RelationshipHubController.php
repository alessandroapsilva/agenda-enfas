<?php

namespace App\Http\Controllers\Enfas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RelationshipHubController extends Controller
{
    public function index(Request $request)
    {
        $start = today();
        $end = today()->endOfDay();

        $appointments = Schema::hasTable('appointments')
            ? DB::table('appointments')->whereBetween('start_at', [$start, $end])
            : null;

        $metrics = [
            'today' => $appointments ? (clone $appointments)->count() : 0,
            'pending' => $appointments
                ? (clone $appointments)
                    ->where(function ($query) {
                        $query->where('confirmation_status', 'pending')
                            ->orWhere('status', 'awaiting_confirmation');
                    })
                    ->count()
                : 0,
            'confirmed' => $appointments
                ? (clone $appointments)
                    ->where(function ($query) {
                        $query->where('confirmation_status', 'confirmed')
                            ->orWhere('status', 'confirmed');
                    })
                    ->count()
                : 0,
            'cancelled' => $appointments
                ? (clone $appointments)->where('status', 'cancelled')->count()
                : 0,
            'waitlist' => Schema::hasTable('waitlist_entries')
                ? DB::table('waitlist_entries')->whereIn('status', ['waiting', 'offered'])->count()
                : 0,
            'messages_today' => Schema::hasTable('wa_messages') && Schema::hasColumn('wa_messages', 'created_at')
                ? DB::table('wa_messages')->whereBetween('created_at', [$start, $end])->count()
                : 0,
        ];

        $attention = collect();

        if ($appointments) {
            $query = DB::table('appointments as a')
                ->leftJoin('patients as p', 'p.id', '=', 'a.patient_id')
                ->leftJoin('professionals as pr', 'pr.id', '=', 'a.professional_id')
                ->leftJoin('services as s', 's.id', '=', 'a.service_id')
                ->whereBetween('a.start_at', [$start, $end])
                ->where(function ($q) {
                    $q->where('a.confirmation_status', 'pending')
                        ->orWhere('a.status', 'awaiting_confirmation');
                })
                ->orderBy('a.start_at')
                ->limit(8);

            $attention = $query->get([
                'a.id',
                'a.code',
                'a.start_at',
                'a.status',
                'a.confirmation_status',
                'p.name as patient_name',
                'p.phone as patient_phone',
                'pr.name as professional_name',
                's.name as service_name',
            ]);
        }

        return view('enfas.relationship.index', compact('metrics', 'attention'));
    }
}
