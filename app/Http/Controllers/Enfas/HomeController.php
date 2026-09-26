<?php

namespace App\Http\Controllers\Enfas;

use App\Http\Controllers\Controller;
use App\Models\MetaIntegration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    private function count(string $table, ?callable $callback = null): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);

        if ($callback) {
            $callback($query);
        }

        return $query->count();
    }

    public function index()
    {
        // V11_ENTRY_REDIRECT
        return redirect()->route('v11.today');

        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $metrics = [
            'today' => $this->count(
                'appointments',
                fn ($q) => $q->whereBetween(
                    'start_at',
                    [$start, $end]
                )
            ),
            'confirmed' => $this->count(
                'appointments',
                fn ($q) => $q
                    ->whereBetween(
                        'start_at',
                        [$start, $end]
                    )
                    ->where('status', 'confirmed')
            ),
            'awaiting' => $this->count(
                'appointments',
                fn ($q) => $q
                    ->whereBetween(
                        'start_at',
                        [$start, $end]
                    )
                    ->whereIn(
                        'status',
                        [
                            'scheduled',
                            'awaiting_confirmation',
                        ]
                    )
            ),
            'patients' => $this->count('patients'),
        ];

        $appointments = collect();

        if (Schema::hasTable('appointments')
            && Schema::hasTable('patients')
            && Schema::hasTable('professionals')
            && Schema::hasTable('services')) {
            $query = DB::table('appointments')
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

            if (Schema::hasTable('locations')
                && Schema::hasColumn(
                    'appointments',
                    'location_id'
                )) {
                $query->leftJoin(
                    'locations',
                    'locations.id',
                    '=',
                    'appointments.location_id'
                );
            }

            $columns = [
                'appointments.id',
                'appointments.code',
                'appointments.start_at',
                'appointments.status',
                'patients.name as patient_name',
                'professionals.name as professional_name',
                'services.name as service_name',
            ];

            if (Schema::hasTable('locations')
                && Schema::hasColumn(
                    'appointments',
                    'location_id'
                )) {
                $columns[] = 'locations.name as location_name';
            }

            $appointments = $query
                ->whereBetween(
                    'appointments.start_at',
                    [$start, $end]
                )
                ->where(
                    'appointments.status',
                    '!=',
                    'cancelled'
                )
                ->orderBy(
                    'appointments.start_at'
                )
                ->limit(10)
                ->get($columns);
        }

        $alerts = Schema::hasTable('system_alerts')
            ? DB::table('system_alerts')
                ->where('is_read', false)
                ->orderByDesc('id')
                ->limit(5)
                ->get()
            : collect();

        $wa = [
            'connected' => false,
            'number' => null,
            'name' => null,
            'quality' => null,
            'templates_approved' => 0,
            'messages_today' => 0,
            'failed_today' => 0,
        ];

        try {
            $integration = MetaIntegration::first();

            if ($integration) {
                $wa['connected'] = (bool)
                    $integration->last_tested_at;

                $wa['number'] =
                    $integration->display_phone_number;

                $wa['name'] =
                    $integration->verified_name;

                $wa['quality'] =
                    $integration->quality_rating;
            }
        } catch (\Throwable) {
        }

        if (Schema::hasTable('wa_templates')) {
            $wa['templates_approved'] =
                DB::table('wa_templates')
                    ->where('status', 'APPROVED')
                    ->count();
        }

        if (Schema::hasTable('wa_messages')) {
            $wa['messages_today'] =
                DB::table('wa_messages')
                    ->whereDate(
                        'created_at',
                        today()
                    )
                    ->count();

            $wa['failed_today'] =
                DB::table('wa_messages')
                    ->whereDate(
                        'created_at',
                        today()
                    )
                    ->where('status', 'failed')
                    ->count();
        }

        return view(
            'enfas.home',
            compact(
                'metrics',
                'appointments',
                'alerts',
                'wa'
            )
        );
    }
}
