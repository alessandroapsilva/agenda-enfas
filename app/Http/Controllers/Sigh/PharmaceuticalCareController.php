<?php

namespace App\Http\Controllers\Sigh;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PharmaceuticalCareController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $patientId = $request->integer('patient_id');

        $patients = Patient::query()
            ->when($q, fn ($query) => $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('preferred_name', 'like', "%{$q}%")
                    ->orWhere('rgea_number', 'like', "%{$q}%")
                    ->orWhere('cpf', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->limit(50)
            ->get();

        $selectedPatient = $patientId ? Patient::find($patientId) : null;

        $medications = collect();
        $pmc = collect();
        $lmes = collect();
        $apacs = collect();

        if ($selectedPatient) {
            $medications = DB::table('sigh_patient_medications')
                ->where('patient_id', $selectedPatient->id)
                ->orderByDesc('is_active')
                ->orderBy('medication_name')
                ->get();

            $pmc = DB::table('sigh_pmc_controls as p')
                ->leftJoin('sigh_patient_medications as m', 'm.id', '=', 'p.patient_medication_id')
                ->where('p.patient_id', $selectedPatient->id)
                ->select('p.*', 'm.medication_name')
                ->orderByDesc('p.is_active')
                ->orderBy('p.estimated_end_at')
                ->get();

            $lmes = DB::table('sigh_lme_requests')
                ->where('patient_id', $selectedPatient->id)
                ->orderByDesc('id')
                ->get();

            $apacs = DB::table('sigh_apac_authorizations')
                ->where('patient_id', $selectedPatient->id)
                ->orderByDesc('id')
                ->get();
        }

        $stats = [
            'active_medications' => DB::table('sigh_patient_medications')->where('is_active', true)->count(),
            'pmc_attention' => DB::table('sigh_pmc_controls')
                ->where('is_active', true)
                ->whereNotNull('estimated_end_at')
                ->whereDate('estimated_end_at', '<=', now()->addDays(7)->toDateString())
                ->count(),
            'lme_attention' => DB::table('sigh_lme_requests')
                ->whereIn('status', ['draft','pending_documents','submitted','under_review'])
                ->count(),
            'apac_attention' => DB::table('sigh_apac_authorizations')
                ->whereIn('status', ['draft','pending','active'])
                ->where(function ($query) {
                    $query->whereNull('authorized_until')
                        ->orWhereDate('authorized_until', '<=', now()->addDays(30)->toDateString());
                })
                ->count(),
        ];

        return view('sigh.pharmacy.index', compact(
            'patients',
            'selectedPatient',
            'medications',
            'pmc',
            'lmes',
            'apacs',
            'stats',
            'q'
        ));
    }

    public function storeMedication(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required','exists:patients,id'],
            'medication_name' => ['required','string','max:180'],
            'dosage' => ['nullable','string','max:120'],
            'route' => ['nullable','string','max:80'],
            'frequency' => ['nullable','string','max:120'],
            'started_at' => ['nullable','date'],
            'ended_at' => ['nullable','date','after_or_equal:started_at'],
            'prescriber_name' => ['nullable','string','max:160'],
            'prescriber_registry' => ['nullable','string','max:80'],
            'notes' => ['nullable','string','max:5000'],
        ]);

        $data['is_active'] = true;
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('sigh_patient_medications')->insert($data);

        return back()->with('success', 'Medicamento incluído no acompanhamento do paciente.');
    }

    public function storePmc(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required','exists:patients,id'],
            'patient_medication_id' => [
                'nullable',
                Rule::exists('sigh_patient_medications', 'id')
                    ->where(fn ($query) => $query->where('patient_id', $request->integer('patient_id'))),
            ],
            'quantity_at_home' => ['nullable','numeric','min:0'],
            'daily_consumption' => ['nullable','numeric','min:0'],
            'unit' => ['nullable','string','max:40'],
            'last_delivery_at' => ['nullable','date'],
            'estimated_end_at' => ['nullable','date'],
            'next_supply_at' => ['nullable','date'],
            'notes' => ['nullable','string','max:5000'],
        ]);

        $data['is_active'] = true;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('sigh_pmc_controls')->insert($data);

        return back()->with('success', 'PMC registrado com sucesso.');
    }

    public function storeLme(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required','exists:patients,id'],
            'medication_name' => ['required','string','max:180'],
            'cid10' => ['nullable','string','max:20'],
            'diagnosis' => ['nullable','string','max:255'],
            'prescriber_name' => ['nullable','string','max:160'],
            'prescriber_registry' => ['nullable','string','max:80'],
            'requested_at' => ['nullable','date'],
            'protocol_number' => ['nullable','string','max:100'],
            'status' => ['required', Rule::in([
                'draft','pending_documents','submitted','under_review',
                'approved','denied','dispensing','renewal_due','closed',
            ])],
            'valid_until' => ['nullable','date'],
            'renewal_due_at' => ['nullable','date'],
            'notes' => ['nullable','string','max:5000'],
        ]);

        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('sigh_lme_requests')->insert($data);

        return back()->with('success', 'LME incluída no acompanhamento.');
    }

    public function storeApac(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required','exists:patients,id'],
            'procedure_code' => ['nullable','string','max:30'],
            'procedure_name' => ['required','string','max:255'],
            'cid10' => ['nullable','string','max:20'],
            'authorization_number' => ['nullable','string','max:100'],
            'competence' => ['nullable','regex:/^\\d{4}-\\d{2}$/'],
            'authorized_from' => ['nullable','date'],
            'authorized_until' => ['nullable','date','after_or_equal:authorized_from'],
            'establishment' => ['nullable','string','max:180'],
            'professional_name' => ['nullable','string','max:160'],
            'status' => ['required', Rule::in(['draft','pending','active','expired','closed','denied'])],
            'notes' => ['nullable','string','max:5000'],
        ]);

        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('sigh_apac_authorizations')->insert($data);

        return back()->with('success', 'APAC incluída no acompanhamento.');
    }
}
