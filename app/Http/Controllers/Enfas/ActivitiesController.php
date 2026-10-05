<?php

namespace App\Http\Controllers\Enfas;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ActivitiesController extends Controller
{
    public function index(Request $request)
    {
        $scope = $request->string('scope')->toString() ?: 'mine';
        $status = $request->string('status')->toString() ?: 'open';
        $when = $request->string('when')->toString();

        $query = DB::table('clinic_tasks as t')
            ->leftJoin('patients as p', 'p.id', '=', 't.patient_id')
            ->leftJoin('appointments as a', 'a.id', '=', 't.appointment_id')
            ->leftJoin('users as u', 'u.id', '=', 't.assigned_user_id')
            ->select([
                't.*',
                'p.name as patient_name',
                'p.rgea_number as patient_rgea',
                'a.code as appointment_code',
                'u.name as assigned_user_name',
            ])
            ->where('t.status', $status === 'completed' ? 'completed' : 'open');

        if ($scope !== 'all' || ! $request->user()->canAccess('activities.manage')) {
            $query->where('t.assigned_user_id', $request->user()->id);
        }

        if ($status !== 'completed') {
            $query->when($when === 'overdue', fn ($q) =>
                $q->whereNotNull('t.due_at')->where('t.due_at', '<', now())
            );

            $query->when($when === 'today', fn ($q) =>
                $q->whereDate('t.due_at', today())
            );

            $query->when($when === 'upcoming', fn ($q) =>
                $q->where('t.due_at', '>', now()->endOfDay())
            );
        }

        $tasks = $query
            ->orderByRaw("CASE t.priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->orderBy('t.due_at')
            ->orderByDesc('t.id')
            ->paginate(40)
            ->withQueryString();

        $statsBase = DB::table('clinic_tasks')->where('status', 'open');

        if ($scope !== 'all' || ! $request->user()->canAccess('activities.manage')) {
            $statsBase->where('assigned_user_id', $request->user()->id);
        }

        $stats = [
            'open' => (clone $statsBase)->count(),
            'overdue' => (clone $statsBase)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count(),
            'today' => (clone $statsBase)
                ->whereDate('due_at', today())
                ->count(),
            'urgent' => (clone $statsBase)
                ->where('priority', 'urgent')
                ->count(),
        ];

        $patients = Patient::query()->orderBy('name')->limit(300)->get(['id','name','rgea_number']);
        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id','name','role']);

        return view('enfas.activities.index', compact(
            'tasks',
            'stats',
            'patients',
            'users',
            'scope',
            'status',
            'when'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required','string','max:180'],
            'notes' => ['nullable','string','max:3000'],
            'priority' => ['required', Rule::in(['low','normal','high','urgent'])],
            'due_at' => ['nullable','date'],
            'patient_id' => ['nullable','exists:patients,id'],
            'assigned_user_id' => ['nullable','exists:users,id'],
        ]);

        DB::table('clinic_tasks')->insert([
            'patient_id' => $data['patient_id'] ?? null,
            'title' => trim($data['title']),
            'notes' => $data['notes'] ?? null,
            'priority' => $data['priority'],
            'status' => 'open',
            'due_at' => $data['due_at'] ?? null,
            'assigned_user_id' => $data['assigned_user_id'] ?? $request->user()->id,
            'created_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Atividade criada.');
    }

    public function complete(Request $request, int $task)
    {
        $row = DB::table('clinic_tasks')->where('id', $task)->first();
        abort_unless($row, 404);

        if (! $request->user()->canAccess('activities.manage')) {
            abort_unless((int) $row->assigned_user_id === (int) $request->user()->id, 403);
        }

        DB::table('clinic_tasks')
            ->where('id', $task)
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Atividade concluída.');
    }
}
