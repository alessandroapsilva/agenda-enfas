<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class ProfessionalWorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user && $user->professional_id,
            403,
            'Este usuário não está vinculado a um profissional.'
        );

        $professional = $user->professional()
            ->with('services')
            ->firstOrFail();

        $todayQuery = Appointment::query()
            ->with(['patient','service'])
            ->where('professional_id', $professional->id)
            ->whereDate('start_at', today());

        $metrics = [
            'today' => (clone $todayQuery)->count(),
            'confirmed' => (clone $todayQuery)->where('status', 'confirmed')->count(),
            'completed' => (clone $todayQuery)->where('status', 'completed')->count(),
            'pending' => (clone $todayQuery)
                ->whereIn('status', ['scheduled','awaiting_confirmation'])
                ->count(),
        ];

        $today = (clone $todayQuery)
            ->orderBy('start_at')
            ->get();

        $upcoming = Appointment::query()
            ->with(['patient','service'])
            ->where('professional_id', $professional->id)
            ->where('start_at', '>', now())
            ->whereNotIn('status', ['cancelled','no_show'])
            ->orderBy('start_at')
            ->limit(12)
            ->get();

        return view('professionals.workspace', compact(
            'professional',
            'metrics',
            'today',
            'upcoming'
        ));
    }
}
