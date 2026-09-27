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
        $user = auth()->user();

        if (
            $user
            && $user->role === 'professional'
            && $user->professional_id
        ) {
            return redirect()->route('professional.workspace');
        }

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
            'pickup_ready' => $this->count(
                'appointments',
                fn ($q) => $q
                    ->where('appointment_type', 'medication_pickup')
                    ->where('pickup_status', 'ready')
            ),
            'professionals' => $this->count(
                'professionals',
                fn ($q) => $q->where('is_active', true)
            ),
            'services' => $this->count(
                'services',
                fn ($q) => $q->where('is_active', true)
            ),
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

        $inbox = [
            'active' => 0,
            'human' => 0,
            'unread' => 0,
        ];

        if (Schema::hasTable('wa_conversations')) {
            $inbox['active'] = DB::table('wa_conversations')
                ->where('status', 'active')
                ->count();

            $inbox['human'] = DB::table('wa_conversations')
                ->where('status', 'active')
                ->where('mode', 'human')
                ->count();

            $inbox['unread'] = (int) DB::table('wa_conversations')
                ->where('status', 'active')
                ->sum('unread_count');
        }

        $weekStart = now()->copy()->startOfWeek();
        $weekEnd = now()->copy()->endOfWeek();

        $week = [
            'total' => $this->count(
                'appointments',
                fn ($q) => $q->whereBetween('start_at', [$weekStart, $weekEnd])
            ),
            'confirmed' => $this->count(
                'appointments',
                fn ($q) => $q->whereBetween('start_at', [$weekStart, $weekEnd])
                    ->where('status', 'confirmed')
            ),
            'cancelled' => $this->count(
                'appointments',
                fn ($q) => $q->whereBetween('start_at', [$weekStart, $weekEnd])
                    ->where('status', 'cancelled')
            ),
            'no_show' => $this->count(
                'appointments',
                fn ($q) => $q->whereBetween('start_at', [$weekStart, $weekEnd])
                    ->where('status', 'no_show')
            ),
        ];

        return view(
            'enfas.home',
            compact(
                'metrics',
                'appointments',
                'alerts',
                'wa',
                'inbox',
                'week'
            )
        );
    }
}
