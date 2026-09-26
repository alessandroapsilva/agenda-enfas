<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Professional;
use App\Models\Service;
use App\Models\WaitlistEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WaitlistController extends Controller
{
    public function index()
    {
        $entries = WaitlistEntry::query()
            ->with(['patient','service','professional','appointment'])
            ->orderByRaw("CASE status WHEN 'offered' THEN 1 WHEN 'waiting' THEN 2 ELSE 3 END")
            ->orderBy('created_at')
            ->paginate(40);

        $patients = Patient::where('is_active', true)->orderBy('name')->get();
        $services = Service::where('is_active', true)->orderBy('name')->get();
        $professionals = Professional::where('is_active', true)->orderBy('name')->get();

        return view('waitlist.index', compact(
            'entries',
            'patients',
            'services',
            'professionals'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required','exists:patients,id'],
            'service_id' => ['required','exists:services,id'],
            'professional_id' => ['nullable','exists:professionals,id'],
            'preferred_period' => ['nullable',Rule::in(['morning','afternoon','evening'])],
            'earliest_date' => ['nullable','date'],
            'latest_date' => ['nullable','date','after_or_equal:earliest_date'],
            'notes' => ['nullable','string','max:2000'],
        ]);

        WaitlistEntry::create($data + ['status' => 'waiting']);

        return back()->with('success', 'Paciente adicionado à lista de espera.');
    }

    public function cancel(WaitlistEntry $entry)
    {
        $entry->update(['status' => 'cancelled']);

        return back()->with('success', 'Entrada removida da lista de espera.');
    }
}
