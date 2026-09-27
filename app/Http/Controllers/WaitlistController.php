<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Patient;
use App\Models\Professional;
use App\Models\Service;
use App\Models\WaitlistEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WaitlistController extends Controller
{
    public function index(Request $request, \App\Services\Enfas\WaitlistService $waitlist)
    {
        $waitlist->expireOldOffers();

        $status = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());
        $locationId = $request->integer('location_id') ?: null;

        $query = WaitlistEntry::query()
            ->with(['patient','service','professional','location','appointment'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('patient', function ($patient) use ($search) {
                        $patient
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('preferred_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('rgea_number', 'like', "%{$search}%");
                    })->orWhereHas('service', function ($service) use ($search) {
                        $service->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->orderByRaw("CASE status WHEN 'offered' THEN 1 WHEN 'waiting' THEN 2 ELSE 3 END")
            ->orderBy('created_at');

        $entries = $query->paginate(40)->withQueryString();

        $metricsBase = WaitlistEntry::query();
        $metrics = [
            'waiting' => (clone $metricsBase)->where('status', 'waiting')->count(),
            'offered' => (clone $metricsBase)->where('status', 'offered')->count(),
            'accepted' => (clone $metricsBase)->where('status', 'accepted')->count(),
            'cancelled' => (clone $metricsBase)->where('status', 'cancelled')->count(),
        ];

        $patients = Patient::where('is_active', true)->orderBy('name')->get();
        $services = Service::where('is_active', true)->orderBy('name')->get();
        $professionals = Professional::where('is_active', true)->orderBy('name')->get();
        $locations = Location::where('is_active', true)->orderByDesc('is_main')->orderBy('name')->get();

        return view('waitlist.index', compact(
            'entries',
            'patients',
            'services',
            'professionals',
            'locations',
            'metrics',
            'status',
            'search',
            'locationId'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required','exists:patients,id'],
            'service_id' => ['required','exists:services,id'],
            'professional_id' => ['nullable','exists:professionals,id'],
            'location_id' => ['nullable','exists:locations,id'],
            'preferred_period' => ['nullable',Rule::in(['morning','afternoon','evening'])],
            'earliest_date' => ['nullable','date'],
            'latest_date' => ['nullable','date','after_or_equal:earliest_date'],
            'notes' => ['nullable','string','max:2000'],
        ]);

        $duplicate = WaitlistEntry::query()
            ->where('patient_id', $data['patient_id'])
            ->where('service_id', $data['service_id'])
            ->whereIn('status', ['waiting','offered'])
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors([
                    'patient_id' => 'Este paciente já possui uma entrada ativa compatível na lista de espera.',
                ]);
        }

        WaitlistEntry::create($data + ['status' => 'waiting']);

        return back()->with('success', 'Paciente adicionado à lista de espera.');
    }

    public function cancel(WaitlistEntry $entry)
    {
        $entry->update(['status' => 'cancelled']);

        return back()->with('success', 'Entrada removida da lista de espera.');
    }
}
