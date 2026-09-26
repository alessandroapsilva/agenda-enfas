<?php

namespace App\Http\Controllers;

use App\Models\Appointment;

class DashboardController extends Controller
{
    public function index()
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $base = Appointment::query()
            ->whereBetween('start_at', [$start, $end]);

        $metrics = [
            'today' => (clone $base)->count(),

            'confirmed' => (clone $base)
                ->where('status', 'confirmed')
                ->count(),

            'awaiting' => (clone $base)
                ->where('status', 'awaiting_confirmation')
                ->count(),

            'cancelled' => (clone $base)
                ->where('status', 'cancelled')
                ->count(),
        ];

        $upcoming = Appointment::query()
            ->with([
                'patient',
                'professional',
                'service',
            ])
            ->whereBetween('start_at', [$start, $end])
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_at')
            ->limit(8)
            ->get();

        return view(
            'dashboard',
            compact('metrics', 'upcoming')
        );
    }
}
