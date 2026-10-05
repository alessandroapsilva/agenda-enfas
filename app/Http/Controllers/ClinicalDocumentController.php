<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ClinicalDocument;
use App\Models\ClinicalDocumentSignature;
use App\Models\ClinicalDocumentTemplate;
use App\Models\Patient;
use App\Services\Enfas\AccessScopeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClinicalDocumentController extends Controller
{
    public function index(Request $request, AccessScopeService $access)
    {
        $patient = null;
        $appointment = null;

        if ($request->filled('patient_id')) {
            $patient = Patient::findOrFail($request->integer('patient_id'));
            abort_unless($access->canViewPatient($request->user(), $patient), 403);
        }

        if ($request->filled('appointment_id')) {
            $appointment = Appointment::with(['patient','professional'])
                ->findOrFail($request->integer('appointment_id'));

            abort_unless($access->canViewAppointment($request->user(), $appointment), 403);
            $patient ??= $appointment->patient;
        }

        $documents = ClinicalDocument::query()
            ->with(['patient','professional','signatures'])
            ->when($patient, fn ($q) => $q->where('patient_id', $patient->id))
            ->when($appointment, fn ($q) => $q->where('appointment_id', $appointment->id))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $templates = ClinicalDocumentTemplate::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('clinical-documents.index', compact(
            'documents','templates','patient','appointment'
        ));
    }

    public function store(Request $request, AccessScopeService $access)
    {
        $data = $request->validate([
            'patient_id' => ['required','exists:patients,id'],
            'appointment_id' => ['nullable','exists:appointments,id'],
            'professional_id' => ['nullable','exists:professionals,id'],
            'template_id' => ['nullable','exists:clinical_document_templates,id'],
            'document_type' => ['required', Rule::in([
                'prescription','certificate','declaration','referral',
                'exam_request','report','orientation','other',
            ])],
            'title' => ['required','string','max:180'],
            'content' => ['required','string','max:30000'],
        ]);

        $patient = Patient::findOrFail($data['patient_id']);
        abort_unless($access->canViewPatient($request->user(), $patient), 403);

        if (! empty($data['appointment_id'])) {
            $appointment = Appointment::findOrFail($data['appointment_id']);
            abort_unless($access->canViewAppointment($request->user(), $appointment), 403);
        }

        $document = ClinicalDocument::create([
            ...$data,
            'status' => 'draft',
            'version' => 1,
            'content_hash' => hash('sha256', $data['content']),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('clinical-documents.show', $document)
            ->with('success', 'Documento criado como rascunho.');
    }

    public function show(Request $request, ClinicalDocument $document, AccessScopeService $access)
    {
        $document->load(['patient','appointment','professional','signatures.user']);

        abort_unless($access->canViewPatient($request->user(), $document->patient), 403);

        if ($document->appointment) {
            abort_unless($access->canViewAppointment($request->user(), $document->appointment), 403);
        }

        return view('clinical-documents.show', compact('document'));
    }

    public function update(Request $request, ClinicalDocument $document, AccessScopeService $access)
    {
        $document->load(['patient','appointment']);
        abort_unless($access->canViewPatient($request->user(), $document->patient), 403);

        if ($document->isLocked()) {
            throw ValidationException::withMessages([
                'document' => 'Documento assinado ou emitido não pode ser alterado.',
            ]);
        }

        $data = $request->validate([
            'title' => ['required','string','max:180'],
            'content' => ['required','string','max:30000'],
        ]);

        $document->update([
            'title' => trim($data['title']),
            'content' => $data['content'],
            'content_hash' => hash('sha256', $data['content']),
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Documento atualizado.');
    }

    public function sign(Request $request, ClinicalDocument $document, AccessScopeService $access)
    {
        $document->load(['patient','appointment','professional']);
        abort_unless($access->canViewPatient($request->user(), $document->patient), 403);

        if ($document->isLocked()) {
            return back()->with('success', 'Documento já está assinado/emitido.');
        }

        if (blank($document->content)) {
            throw ValidationException::withMessages([
                'document' => 'Documento sem conteúdo não pode ser assinado.',
            ]);
        }

        $hash = hash('sha256', $document->content);

        ClinicalDocumentSignature::create([
            'clinical_document_id' => $document->id,
            'signature_type' => 'electronic',
            'user_id' => $request->user()->id,
            'signer_name' => $request->user()->name,
            'signer_registry' => $document->professional
                ? trim(implode(' ', array_filter([
                    $document->professional->council_type,
                    $document->professional->council_number,
                    $document->professional->council_state,
                ])))
                : null,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
            'document_hash' => $hash,
            'provider' => 'agenda_enfas',
            'metadata' => [
                'auth_user_id' => $request->user()->id,
                'document_version' => $document->version,
            ],
            'signed_at' => now(),
        ]);

        $document->update([
            'status' => 'signed',
            'signed_at' => now(),
            'issued_at' => now(),
            'content_hash' => $hash,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Documento assinado eletronicamente e bloqueado para edição.');
    }

    public function print(Request $request, ClinicalDocument $document, AccessScopeService $access)
    {
        $document->load(['patient','appointment','professional','signatures.user']);
        abort_unless($access->canViewPatient($request->user(), $document->patient), 403);

        return view('clinical-documents.print', compact('document'));
    }
}
