<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::query()
            ->withCount('professionals')
            ->orderBy('name')
            ->get();

        return view('services.index', compact('services'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Service::create([
            'name' => trim($data['name']),
            'code' => $data['code'] ? Str::upper(trim($data['code'])) : null,
            'duration_minutes' => $data['duration_minutes'],
            'description' => $data['description'] ?? null,
            'arrival_minutes' => $data['arrival_minutes'],
            'required_documents' => $data['required_documents'] ?? null,
            'preparation_instructions' => $data['preparation_instructions'] ?? null,
            'aftercare_instructions' => $data['aftercare_instructions'] ?? null,
            'allow_online_reschedule' => (bool) ($data['allow_online_reschedule'] ?? false),
            'allow_recurrence' => (bool) ($data['allow_recurrence'] ?? false),
            'color' => $data['color'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Serviço cadastrado com sucesso.');
    }

    public function update(Request $request, Service $service)
    {
        $data = $this->validated($request, $service);

        $service->update([
            'name' => trim($data['name']),
            'code' => $data['code'] ? Str::upper(trim($data['code'])) : null,
            'duration_minutes' => $data['duration_minutes'],
            'description' => $data['description'] ?? null,
            'arrival_minutes' => $data['arrival_minutes'],
            'required_documents' => $data['required_documents'] ?? null,
            'preparation_instructions' => $data['preparation_instructions'] ?? null,
            'aftercare_instructions' => $data['aftercare_instructions'] ?? null,
            'allow_online_reschedule' => (bool) ($data['allow_online_reschedule'] ?? false),
            'allow_recurrence' => (bool) ($data['allow_recurrence'] ?? false),
            'color' => $data['color'],
        ]);

        return back()->with('success', 'Serviço atualizado.');
    }

    public function toggle(Service $service)
    {
        $service->update(['is_active' => ! $service->is_active]);

        return back()->with(
            'success',
            $service->is_active ? 'Serviço ativado.' : 'Serviço desativado.'
        );
    }

    private function validated(Request $request, ?Service $service = null): array
    {
        return $request->validate([
            'name' => ['required','string','max:160'],
            'code' => [
                'nullable','string','max:60',
                Rule::unique('services','code')->ignore($service?->id),
            ],
            'duration_minutes' => ['required','integer','min:5','max:720'],
            'arrival_minutes' => ['required','integer','min:0','max:240'],
            'description' => ['nullable','string','max:5000'],
            'required_documents' => ['nullable','string','max:5000'],
            'preparation_instructions' => ['nullable','string','max:5000'],
            'aftercare_instructions' => ['nullable','string','max:5000'],
            'allow_online_reschedule' => ['nullable','boolean'],
            'allow_recurrence' => ['nullable','boolean'],
            'color' => ['required','regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);
    }
}
