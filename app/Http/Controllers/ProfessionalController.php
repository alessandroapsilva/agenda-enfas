<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\Service;
use Illuminate\Http\Request;

class ProfessionalController extends Controller
{
    public function index()
    {
        $professionals = Professional::query()
            ->with('services')
            ->orderBy('name')
            ->get();

        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'professionals.index',
            compact('professionals', 'services')
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'specialty' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'work_start' => ['required', 'date_format:H:i'],
            'work_end' => ['required', 'date_format:H:i'],
            'slot_interval' => ['required', 'integer', 'min:5', 'max:240'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'services' => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:services,id'],
        ]);

        $professional = Professional::create([
            'name' => trim($data['name']),
            'specialty' => $data['specialty'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'work_start' => $data['work_start'],
            'work_end' => $data['work_end'],
            'slot_interval' => $data['slot_interval'],
            'color' => $data['color'],
            'active_days' => [1, 2, 3, 4, 5],
            'is_active' => true,
        ]);

        $professional
            ->services()
            ->sync($data['services'] ?? []);

        return back()->with(
            'success',
            'Profissional cadastrado com sucesso.'
        );
    }
}
