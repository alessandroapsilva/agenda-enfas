<?php

namespace App\Http\Controllers\Sigh;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Enfas\AccessScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PharmaceuticalCareController extends Controller
{
    public function index(Request $request, AccessScopeService $access)
    {
        $q = trim((string) $request->get('q'));
        $patientId = $request->integer('patient_id');

        $patients = $access->patients(Patient::query(), $request->user())
            ->when($q, fn ($query) => $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('preferred_name', 'like', "%{$q}%")
                    ->orWhere('rgea_number', 'like', "%{$q}%")
                    ->orWhere('cpf', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->limit(50)
            ->get();

        $selectedPatient = $patientId
            ? $access->patients(Patient::query(), $request->user())->find($patientId)
            : null;

        $medications = collect();
        $pmc = collect();
        $lmes = collect();
        $apacs = collect();
        $documents = collect();
        $events = collect();

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

            $documents = DB::table('sigh_patient_documents')
                ->where('patient_id', $selectedPatient->id)
                ->orderByDesc('document_date')
                ->orderByDesc('id')
                ->get();

            $events = DB::table('sigh_care_events as e')
                ->leftJoin('users as u', 'u.id', '=', 'e.user_id')
                ->where('e.patient_id', $selectedPatient->id)
                ->orderByDesc('e.occurred_at')
                ->limit(30)
                ->get([
                    'e.*',
                    'u.name as user_name',
                ]);
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
            'documents',
            'events',
            'stats',
            'q'
        ));
    }

    public function storeMedication(Request $request, AccessScopeService $access)
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
            'requires_special_control' => ['nullable','boolean'],
            'control_category' => ['nullable','string','max:80'],
            'prescription_number' => ['nullable','string','max:100'],
            'prescription_type' => ['nullable','string','max:80'],
            'prescription_issued_at' => ['nullable','date'],
            'prescription_valid_until' => ['nullable','date','after_or_equal:prescription_issued_at'],
            'notes' => ['nullable','string','max:5000'],
        ]);

        $patient = Patient::findOrFail((int) $data['patient_id']);
        abort_unless($access->canViewPatient($request->user(), $patient), 403);

        $data['requires_special_control'] = (bool) ($data['requires_special_control'] ?? false);
        $data['is_active'] = true;
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('sigh_patient_medications')->insertGetId($data);

        $this->recordEvent(
            (int) $data['patient_id'],
            'medication',
            $id,
            'created',
            null,
            'active',
            $request->user()?->id
        );

        return back()->with('success', 'Medicamento incluído no acompanhamento do paciente.');
    }

    public function storePmc(Request $request, AccessScopeService $access)
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

        $patient = Patient::findOrFail((int) $data['patient_id']);
        abort_unless($access->canViewPatient($request->user(), $patient), 403);

        $data['is_active'] = true;
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('sigh_pmc_controls')->insertGetId($data);

        $this->recordEvent(
            (int) $data['patient_id'],
            'pmc',
            $id,
            'snapshot_created',
            null,
            'active',
            $request->user()?->id
        );

        return back()->with('success', 'PMC registrado com sucesso.');
    }

    public function storeLme(Request $request, AccessScopeService $access)
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

        $patient = Patient::findOrFail((int) $data['patient_id']);
        abort_unless($access->canViewPatient($request->user(), $patient), 403);

        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('sigh_lme_requests')->insertGetId($data);

        $this->recordEvent(
            (int) $data['patient_id'],
            'lme',
            $id,
            'created',
            null,
            $data['status'],
            $request->user()?->id
        );

        return back()->with('success', 'LME incluída no acompanhamento.');
    }

    public function storeApac(Request $request, AccessScopeService $access)
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

        $patient = Patient::findOrFail((int) $data['patient_id']);
        abort_unless($access->canViewPatient($request->user(), $patient), 403);

        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('sigh_apac_authorizations')->insertGetId($data);

        $this->recordEvent(
            (int) $data['patient_id'],
            'apac',
            $id,
            'created',
            null,
            $data['status'],
            $request->user()?->id
        );

        return back()->with('success', 'APAC incluída no acompanhamento.');
    }

    public function storeDocument(Request $request, AccessScopeService $access)
    {
        $data = $request->validate([
            'patient_id' => ['required','exists:patients,id'],
            'category' => ['required', Rule::in([
                'prescription','lme','apac','exam','report','authorization','identity','other',
            ])],
            'title' => ['required','string','max:180'],
            'document_date' => ['nullable','date'],
            'valid_until' => ['nullable','date'],
            'notes' => ['nullable','string','max:5000'],
            'file' => ['required','file','mimes:pdf,jpg,jpeg,png','max:15360'],
        ]);

        $patient = Patient::findOrFail((int) $data['patient_id']);
        abort_unless($access->canViewPatient($request->user(), $patient), 403);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $path = 'sigh/patients/'.$data['patient_id'].'/'.Str::uuid().'.'.$extension;

        Storage::disk('local')->put($path, $file->get());

        $documentId = DB::table('sigh_patient_documents')->insertGetId([
            'patient_id' => $data['patient_id'],
            'category' => $data['category'],
            'title' => trim($data['title']),
            'original_name' => $file->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'document_date' => $data['document_date'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->recordEvent(
            (int) $data['patient_id'],
            'document',
            $documentId,
            'uploaded',
            null,
            null,
            $request->user()?->id,
            trim($data['title'])
        );

        return back()->with('success', 'Documento anexado ao paciente.');
    }

    public function updateMedicationStatus(Request $request, int $medication, AccessScopeService $access)
    {
        $row = DB::table('sigh_patient_medications')->where('id', $medication)->first();

        abort_unless($row, 404);

        $patient = Patient::findOrFail((int) $row->patient_id);
        abort_unless($access->canViewPatient($request->user(), $patient), 403);

        $data = $request->validate([
            'is_active' => ['required','boolean'],
        ]);

        DB::table('sigh_patient_medications')
            ->where('id', $medication)
            ->update([
                'is_active' => (bool) $data['is_active'],
                'ended_at' => $data['is_active'] ? null : ($row->ended_at ?: now()->toDateString()),
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

        $this->recordEvent(
            (int) $row->patient_id,
            'medication',
            $medication,
            'status_changed',
            $row->is_active ? 'active' : 'closed',
            $data['is_active'] ? 'active' : 'closed',
            $request->user()?->id
        );

        return back()->with('success', $data['is_active'] ? 'Medicamento reativado.' : 'Medicamento encerrado.');
    }

    public function updateLmeStatus(Request $request, int $lme, AccessScopeService $access)
    {
        $row = DB::table('sigh_lme_requests')->where('id', $lme)->first();

        abort_unless($row, 404);

        $patient = Patient::findOrFail((int) $row->patient_id);
        abort_unless($access->canViewPatient($request->user(), $patient), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                'draft','pending_documents','submitted','under_review',
                'approved','denied','dispensing','renewal_due','closed',
            ])],
            'protocol_number' => ['nullable','string','max:100'],
            'valid_until' => ['nullable','date'],
            'renewal_due_at' => ['nullable','date'],
        ]);

        DB::table('sigh_lme_requests')
            ->where('id', $lme)
            ->update([
                'status' => $data['status'],
                'protocol_number' => $data['protocol_number'] ?? $row->protocol_number,
                'valid_until' => $data['valid_until'] ?? $row->valid_until,
                'renewal_due_at' => $data['renewal_due_at'] ?? $row->renewal_due_at,
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

        $this->recordEvent(
            (int) $row->patient_id,
            'lme',
            $lme,
            'status_changed',
            $row->status,
            $data['status'],
            $request->user()?->id
        );

        return back()->with('success', 'Situação da LME atualizada.');
    }

    public function updateApacStatus(Request $request, int $apac, AccessScopeService $access)
    {
        $row = DB::table('sigh_apac_authorizations')->where('id', $apac)->first();

        abort_unless($row, 404);

        $patient = Patient::findOrFail((int) $row->patient_id);
        abort_unless($access->canViewPatient($request->user(), $patient), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['draft','pending','active','expired','closed','denied'])],
            'authorization_number' => ['nullable','string','max:100'],
            'authorized_until' => ['nullable','date'],
        ]);

        DB::table('sigh_apac_authorizations')
            ->where('id', $apac)
            ->update([
                'status' => $data['status'],
                'authorization_number' => $data['authorization_number'] ?? $row->authorization_number,
                'authorized_until' => $data['authorized_until'] ?? $row->authorized_until,
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

        $this->recordEvent(
            (int) $row->patient_id,
            'apac',
            $apac,
            'status_changed',
            $row->status,
            $data['status'],
            $request->user()?->id
        );

        return back()->with('success', 'Situação da APAC atualizada.');
    }

    public function downloadDocument(Request $request, int $document, AccessScopeService $access)
    {
        $row = DB::table('sigh_patient_documents')->where('id', $document)->first();

        abort_unless($row, 404);
        $patient = Patient::findOrFail((int) $row->patient_id);
        abort_unless($access->canViewPatient($request->user(), $patient), 403);

        abort_unless(Storage::disk($row->disk)->exists($row->path), 404);

        return Storage::disk($row->disk)->download(
            $row->path,
            $row->original_name,
            ['Content-Type' => $row->mime_type ?: 'application/octet-stream']
        );
    }


    private function recordEvent(
        int $patientId,
        string $subjectType,
        ?int $subjectId,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        ?int $userId,
        ?string $notes = null
    ): void {
        DB::table('sigh_care_events')->insert([
            'patient_id' => $patientId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'notes' => $notes,
            'user_id' => $userId,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

}
