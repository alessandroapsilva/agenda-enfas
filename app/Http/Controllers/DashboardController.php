<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $metrics = [
            'patients' => 0,
            'appointments_today' => 0,
            'waiting' => 0,
            'active_admissions' => 0,
            'open_prescriptions' => 0,
            'available_beds' => 0,
            'occupied_beds' => 0,
            'pending_dispensations' => 0,
            'low_stock' => 0,
            'expiring_lots' => 0,
        ];

        $recentAppointments = collect();
        $expiringLots = collect();

        try {
            if (Schema::hasTable('patients')) {
                $metrics['patients'] = DB::table('patients')->count();
            }

            if (Schema::hasTable('appointments')) {
                $metrics['appointments_today'] = DB::table('appointments')
                    ->whereDate('starts_at', today())
                    ->count();

                $recentAppointments = DB::table('appointments')
                    ->leftJoin('patients', 'appointments.patient_id', '=', 'patients.id')
                    ->select(
                        'appointments.id',
                        'appointments.starts_at',
                        'appointments.status',
                        'appointments.type',
                        'patients.name as patient_name'
                    )
                    ->whereDate('appointments.starts_at', today())
                    ->orderBy('appointments.starts_at')
                    ->limit(6)
                    ->get();
            }

            if (Schema::hasTable('queue_tickets')) {
                $metrics['waiting'] = DB::table('queue_tickets')
                    ->where('status', 'waiting')
                    ->count();
            }

            if (Schema::hasTable('admissions')) {
                $metrics['active_admissions'] = DB::table('admissions')
                    ->where('status', 'active')
                    ->count();
            }

            if (Schema::hasTable('prescriptions')) {
                $metrics['open_prescriptions'] = DB::table('prescriptions')
                    ->whereIn('status', ['draft', 'signed', 'active'])
                    ->count();
            }

            if (Schema::hasTable('beds')) {
                $metrics['available_beds'] = DB::table('beds')
                    ->where('status', 'available')
                    ->count();

                $metrics['occupied_beds'] = DB::table('beds')
                    ->where('status', 'occupied')
                    ->count();
            }

            if (Schema::hasTable('dispensations')) {
                $metrics['pending_dispensations'] = DB::table('dispensations')
                    ->whereIn('status', ['pending', 'processing'])
                    ->count();
            }

            if (Schema::hasTable('medications') && Schema::hasTable('stock_lots')) {
                $metrics['low_stock'] = DB::table('medications')
                    ->leftJoin('stock_lots', function ($join): void {
                        $join->on('medications.id', '=', 'stock_lots.medication_id')
                            ->where('stock_lots.status', '=', 'available');
                    })
                    ->select('medications.id', 'medications.minimum_stock')
                    ->groupBy('medications.id', 'medications.minimum_stock')
                    ->havingRaw('COALESCE(SUM(stock_lots.quantity), 0) <= medications.minimum_stock')
                    ->get()
                    ->count();

                $metrics['expiring_lots'] = DB::table('stock_lots')
                    ->where('quantity', '>', 0)
                    ->whereNotNull('expires_at')
                    ->whereDate('expires_at', '<=', now()->addDays(90))
                    ->count();

                $expiringLots = DB::table('stock_lots')
                    ->join('medications', 'stock_lots.medication_id', '=', 'medications.id')
                    ->select(
                        'medications.name',
                        'stock_lots.batch',
                        'stock_lots.expires_at',
                        'stock_lots.quantity'
                    )
                    ->where('stock_lots.quantity', '>', 0)
                    ->whereNotNull('stock_lots.expires_at')
                    ->whereDate('stock_lots.expires_at', '<=', now()->addDays(90))
                    ->orderBy('stock_lots.expires_at')
                    ->limit(5)
                    ->get();
            }
        } catch (Throwable) {
            // Mantém o dashboard disponível mesmo durante manutenção/migrations.
        }

        return view('dashboard', [
            'metrics' => $metrics,
            'recentAppointments' => $recentAppointments,
            'expiringLots' => $expiringLots,
            'generatedAt' => now(),
        ]);
    }
}
