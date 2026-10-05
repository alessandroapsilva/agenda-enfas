<?php

namespace App\Http\Controllers\Enfas;

use App\Http\Controllers\Controller;
use App\Models\MetaIntegration;
use Illuminate\Http\Request;
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

    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user && $user->role === 'professional') {
            return redirect()->route('professional.workspace');
        }

        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $locations = Schema::hasTable('locations')
            ? DB::table('locations')
                ->where('is_active', true)
                ->orderByDesc('is_main')
                ->orderBy('name')
                ->get(['id','name','code','is_main'])
            : collect();

        $locationId = $request->integer('location_id') ?: null;

        if ($locationId && ! $locations->contains('id', $locationId)) {
            $locationId = null;
        }

        $scopeLocation = function ($query) use ($locationId) {
            if (
                $locationId
                && Schema::hasColumn('appointments', 'location_id')
            ) {
                $query->where('location_id', $locationId);
            }
        };

        $metrics = [
            'today' => $this->count(
                'appointments',
                function ($q) use ($start, $end, $scopeLocation) {
                    $q->whereBetween('start_at', [$start, $end]);
                    $scopeLocation($q);
                }
            ),
            'confirmed' => $this->count(
                'appointments',
                function ($q) use ($start, $end, $scopeLocation) {
                    $q->whereBetween('start_at', [$start, $end])
                        ->where('status', 'confirmed');
                    $scopeLocation($q);
                }
            ),
            'awaiting' => $this->count(
                'appointments',
                function ($q) use ($start, $end, $scopeLocation) {
                    $q->whereBetween('start_at', [$start, $end])
                        ->whereIn('status', ['scheduled','awaiting_confirmation']);
                    $scopeLocation($q);
                }
            ),
            'patients' => $this->count('patients'),
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

            if (
                $locationId
                && Schema::hasColumn('appointments', 'location_id')
            ) {
                $query->where('appointments.location_id', $locationId);
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

        $engagement = [
            'waiting' => 0,
            'waiting_15' => 0,
            'open_tasks' => 0,
            'overdue_tasks' => 0,
            'urgent_conversations' => 0,
            'follow_up' => 0,
            'avg_first_response_minutes' => null,
        ];

        if (Schema::hasTable('wa_conversations')) {
            $engagement['waiting'] = DB::table('wa_conversations')
                ->where('status', 'active')
                ->whereNotNull('last_inbound_at')
                ->where(function ($q) {
                    $q->whereNull('last_outbound_at')
                        ->orWhereColumn('last_inbound_at', '>', 'last_outbound_at');
                })
                ->count();

            $engagement['waiting_15'] = DB::table('wa_conversations')
                ->where('status', 'active')
                ->whereNotNull('last_inbound_at')
                ->where('last_inbound_at', '<=', now()->subMinutes(15))
                ->where(function ($q) {
                    $q->whereNull('last_outbound_at')
                        ->orWhereColumn('last_inbound_at', '>', 'last_outbound_at');
                })
                ->count();

            if (Schema::hasColumn('wa_conversations', 'priority')) {
                $engagement['urgent_conversations'] = DB::table('wa_conversations')
                    ->where('status', 'active')
                    ->where('priority', 'urgent')
                    ->count();
            }

            if (Schema::hasColumn('wa_conversations', 'lead_stage')) {
                $engagement['follow_up'] = DB::table('wa_conversations')
                    ->where('status', 'active')
                    ->where('lead_stage', 'follow_up')
                    ->count();
            }

            if (
                Schema::hasColumn('wa_conversations', 'first_inbound_at')
                && Schema::hasColumn('wa_conversations', 'first_response_at')
            ) {
                $samples = DB::table('wa_conversations')
                    ->whereNotNull('first_inbound_at')
                    ->whereNotNull('first_response_at')
                    ->where('first_response_at', '>=', now()->subDays(30))
                    ->orderByDesc('first_response_at')
                    ->limit(500)
                    ->get(['first_inbound_at','first_response_at']);

                if ($samples->isNotEmpty()) {
                    $engagement['avg_first_response_minutes'] = round(
                        $samples->avg(function ($row) {
                            $inbound = \Illuminate\Support\Carbon::parse($row->first_inbound_at);
                            $response = \Illuminate\Support\Carbon::parse($row->first_response_at);

                            return max(0, $inbound->diffInSeconds($response, false)) / 60;
                        }),
                        1
                    );
                }
            }
        }

        if (Schema::hasTable('clinic_tasks')) {
            $engagement['open_tasks'] = DB::table('clinic_tasks')
                ->where('status', 'open')
                ->count();

            $engagement['overdue_tasks'] = DB::table('clinic_tasks')
                ->where('status', 'open')
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count();
        }

        $weekStart = now()->copy()->startOfWeek();
        $weekEnd = now()->copy()->endOfWeek();

        $experience = [
            'responses' => 0,
            'nps' => null,
            'stars' => null,
        ];

        if (
            Schema::hasTable('appointments')
            && Schema::hasColumn('appointments', 'satisfaction_score')
        ) {
            $experienceBase = DB::table('appointments')
                ->where('satisfaction_at', '>=', now()->subDays(30));

            $scopeLocation($experienceBase);

            $scores = (clone $experienceBase)
                ->whereNotNull('satisfaction_score')
                ->pluck('satisfaction_score')
                ->map(fn ($score) => (int) $score);

            $experience['responses'] = $scores->count();

            if ($scores->isNotEmpty()) {
                $promoters = $scores->filter(fn ($score) => $score >= 9)->count();
                $detractors = $scores->filter(fn ($score) => $score <= 6)->count();

                $experience['nps'] = (int) round(
                    (($promoters / $scores->count()) * 100)
                    - (($detractors / $scores->count()) * 100)
                );
            }

            if (Schema::hasColumn('appointments', 'satisfaction_stars')) {
                $stars = (clone $experienceBase)
                    ->whereNotNull('satisfaction_stars')
                    ->avg('satisfaction_stars');

                $experience['stars'] = $stars !== null
                    ? round((float) $stars, 1)
                    : null;
            }
        }

        $week = [
            'total' => $this->count(
                'appointments',
                function ($q) use ($weekStart, $weekEnd, $scopeLocation) {
                    $q->whereBetween('start_at', [$weekStart, $weekEnd]);
                    $scopeLocation($q);
                }
            ),
            'confirmed' => $this->count(
                'appointments',
                function ($q) use ($weekStart, $weekEnd, $scopeLocation) {
                    $q->whereBetween('start_at', [$weekStart, $weekEnd])
                        ->where('status', 'confirmed');
                    $scopeLocation($q);
                }
            ),
            'cancelled' => $this->count(
                'appointments',
                function ($q) use ($weekStart, $weekEnd, $scopeLocation) {
                    $q->whereBetween('start_at', [$weekStart, $weekEnd])
                        ->where('status', 'cancelled');
                    $scopeLocation($q);
                }
            ),
            'no_show' => $this->count(
                'appointments',
                function ($q) use ($weekStart, $weekEnd, $scopeLocation) {
                    $q->whereBetween('start_at', [$weekStart, $weekEnd])
                        ->where('status', 'no_show');
                    $scopeLocation($q);
                }
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
                'week',
                'experience',
                'locations',
                'locationId',
                'engagement'
            )
        );
    }
}
