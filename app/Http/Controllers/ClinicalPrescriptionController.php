<?php

namespace App\Http\Controllers;

use App\Contracts\ClinicalSignatureProvider;
use App\Models\Appointment;
use App\Models\AppointmentEvent;
use App\Models\ClinicalDocument;
use App\Models\ClinicalPrescription;
use App\Models\ClinicalPrescriptionItem;
use App\Models\Patient;
use App\Models\Professional;
use App\Services\Enfas\AccessScopeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClinicalPrescriptionController extends Controller
{
    public function index(Request $request, AccessScopeService $access)
    {
        $patient = null;
        $appointment = null;

        if ($request->filled('appointment_id')) {
            $appointment = Appointment::with(['patient', 'professional'])
                ->findOrFail($request->integer('appointment_id'));

            abort_unless($access->canViewAppointment($request->user(), $appointment), 403);
            $patient = $appointment->patient;
        } elseif ($request->filled('patient_id')) {
            $patient = Patient::findOrFail($request->integer('patient_id'));
            abort_unless($access->canViewPatient($request->user(), $patient), 403);
        }

        $prescriptions = ClinicalPrescription::query()
            ->with(['patient', 'professional', 'items'])
            ->when($request->user()->role === 'professional', function ($query) use ($request) {
                $query->where('professional_id', $request->user()->professional_id ?: 0);
            })
            ->when($patient, fn ($query) => $query->where('patient_id', $patient->id))
            ->when($appointment, fn ($query) => $query->where('appointment_id', $appointment->id))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $patients = $access->patients(
            Patient::query()->where('is_active', true),
            $request->user()
        )->orderBy('name')->limit(250)->get();

        $professionals = $access->professionals(
            Professional::query()->where('is_active', true),
            $request->user()
        )->orderBy('name')->get();

        return view('clinical-prescriptions.index', compact(
            'prescriptions',
            'patient',
            'appointment',
            'patients',
            'professionals'
        ));
    }

    public function store(Request $request, AccessScopeService $access)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'professional_id' => ['nullable', 'exists:professionals,id'],
            'title' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:8000'],
        ]);

        $patient = Patient::findOrFail($data['patient_id']);
        abort_unless($access->canViewPatient($request->user(), $patient), 403);

        $appointment = null;
        $professionalId = isset($data['professional_id']) ? (int) $data['professional_id'] : null;

        if (! empty($data['appointment_id'])) {
            $appointment = Appointment::findOrFail($data['appointment_id']);
            abort_unless($access->canViewAppointment($request->user(), $appointment), 403);

            if ((int) $appointment->patient_id !== (int) $patient->id) {
                throw ValidationException::withMessages([
                    'appointment_id' => 'O agendamento informado não pertence ao paciente selecionado.',
                ]);
            }

            $professionalId = (int) $appointment->professional_id;
        }

        if ($request->user()->role === 'professional') {
            abort_unless($request->user()->professional_id, 403);
            $professionalId = (int) $request->user()->professional_id;
        }

        if (! $professionalId) {
            throw ValidationException::withMessages([
                'professional_id' => 'Selecione o profissional responsável pela prescrição.',
            ]);
        }

        abort_unless($access->canUseProfessional($request->user(), $professionalId), 403);

        $prescription = ClinicalPrescription::create([
            'patient_id' => $patient->id,
            'appointment_id' => $appointment?->id,
            'professional_id' => $professionalId,
            'prescription_type' => 'simple',
            'title' => trim((string) ($data['title'] ?? '')) ?: 'Prescrição',
            'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
            'status' => 'draft',
            'version' => 1,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        if ($appointment) {
            AppointmentEvent::create([
                'appointment_id' => $appointment->id,
                'user_id' => $request->user()->id,
                'event_type' => 'prescription_created',
                'title' => 'Prescrição iniciada',
                'description' => 'Nova prescrição criada como rascunho.',
                'metadata' => ['prescription_id' => $prescription->id],
                'occurred_at' => now(),
            ]);
        }

        return redirect()
            ->route('clinical-prescriptions.show', $prescription)
            ->with('success', 'Prescrição criada como rascunho.');
    }

    public function show(Request $request, ClinicalPrescription $prescription, AccessScopeService $access)
    {
        $prescription->load([
            'patient',
            'appointment',
            'professional',
            'items',
            'document.signatures.user',
        ]);

        $this->authorizeScope($request, $prescription, $access);

        return view('clinical-prescriptions.show', compact('prescription'));
    }

    public function update(Request $request, ClinicalPrescription $prescription, AccessScopeService $access)
    {
        $prescription->load(['patient', 'professional']);
        $this->authorizeScope($request, $prescription, $access);
        $this->ensureEditable($prescription);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:8000'],
        ]);

        $prescription->update([
            'title' => trim($data['title']),
            'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Dados da prescrição atualizados.');
    }

    public function storeItem(Request $request, ClinicalPrescription $prescription, AccessScopeService $access)
    {
        $prescription->load(['patient', 'professional']);
        $this->authorizeScope($request, $prescription, $access);
        $this->ensureEditable($prescription);

        $data = $this->validateItem($request);
        $nextOrder = ((int) $prescription->items()->max('sort_order')) + 1;

        $prescription->items()->create([
            ...$data,
            'sort_order' => max(1, $nextOrder),
        ]);

        $prescription->update(['updated_by' => $request->user()->id]);

        return back()->with('success', 'Item adicionado à prescrição.');
    }

    public function updateItem(
        Request $request,
        ClinicalPrescription $prescription,
        ClinicalPrescriptionItem $item,
        AccessScopeService $access
    ) {
        abort_unless((int) $item->clinical_prescription_id === (int) $prescription->id, 404);

        $prescription->load(['patient', 'professional']);
        $this->authorizeScope($request, $prescription, $access);
        $this->ensureEditable($prescription);

        $item->update($this->validateItem($request));
        $prescription->update(['updated_by' => $request->user()->id]);

        return back()->with('success', 'Item atualizado.');
    }

    public function destroyItem(
        Request $request,
        ClinicalPrescription $prescription,
        ClinicalPrescriptionItem $item,
        AccessScopeService $access
    ) {
        abort_unless((int) $item->clinical_prescription_id === (int) $prescription->id, 404);

        $prescription->load(['patient', 'professional']);
        $this->authorizeScope($request, $prescription, $access);
        $this->ensureEditable($prescription);

        $item->delete();
        $prescription->update(['updated_by' => $request->user()->id]);

        return back()->with('success', 'Item removido.');
    }

    public function sign(
        Request $request,
        ClinicalPrescription $prescription,
        AccessScopeService $access,
        ClinicalSignatureProvider $signatures
    ) {
        $prescription->load(['patient', 'appointment', 'professional', 'items', 'document']);
        $this->authorizeScope($request, $prescription, $access);

        if ($prescription->isLocked()) {
            return back()->with('success', 'A prescrição já está assinada e bloqueada.');
        }

        if (! $request->user()->professional_id
            || (int) $request->user()->professional_id !== (int) $prescription->professional_id) {
            throw ValidationException::withMessages([
                'prescription' => 'A assinatura deve ser realizada pelo profissional responsável, autenticado em sua própria conta.',
            ]);
        }

        if (blank($prescription->professional?->council_type)
            || blank($prescription->professional?->council_number)) {
            throw ValidationException::withMessages([
                'prescription' => 'Cadastre conselho profissional e número de registro antes de assinar.',
            ]);
        }

        if ($prescription->items->isEmpty()) {
            throw ValidationException::withMessages([
                'prescription' => 'Adicione ao menos um item antes de assinar.',
            ]);
        }

        $content = $this->renderContent($prescription);
        $hash = hash('sha256', $content);

        $document = $prescription->document;

        if (! $document) {
            $document = ClinicalDocument::create([
                'patient_id' => $prescription->patient_id,
                'appointment_id' => $prescription->appointment_id,
                'professional_id' => $prescription->professional_id,
                'document_type' => 'prescription',
                'title' => $prescription->title,
                'content' => $content,
                'status' => 'draft',
                'version' => $prescription->version,
                'content_hash' => $hash,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $prescription->clinical_document_id = $document->id;
            $prescription->save();
        } else {
            if ($document->isLocked()) {
                throw ValidationException::withMessages([
                    'prescription' => 'O documento vinculado já está bloqueado para edição.',
                ]);
            }

            $document->update([
                'title' => $prescription->title,
                'content' => $content,
                'content_hash' => $hash,
                'updated_by' => $request->user()->id,
            ]);
        }

        $document->load('professional');
        $signature = $signatures->sign($document, $request->user(), $request);

        $document->update([
            'status' => 'signed',
            'signed_at' => $signature->signed_at,
            'issued_at' => $signature->signed_at,
            'content_hash' => $signature->document_hash,
            'external_provider' => $signatures->name(),
            'updated_by' => $request->user()->id,
        ]);

        $prescription->update([
            'status' => 'signed',
            'signed_at' => $signature->signed_at,
            'issued_at' => $signature->signed_at,
            'content_hash' => $signature->document_hash,
            'external_provider' => $signatures->name(),
            'updated_by' => $request->user()->id,
        ]);

        if ($prescription->appointment_id) {
            AppointmentEvent::create([
                'appointment_id' => $prescription->appointment_id,
                'user_id' => $request->user()->id,
                'event_type' => 'prescription_signed',
                'title' => 'Prescrição assinada',
                'description' => 'Prescrição emitida e bloqueada para alterações.',
                'metadata' => [
                    'prescription_id' => $prescription->id,
                    'clinical_document_id' => $document->id,
                    'content_hash' => $signature->document_hash,
                    'provider' => $signatures->name(),
                ],
                'occurred_at' => now(),
            ]);
        }

        return back()->with('success', 'Prescrição assinada eletronicamente e bloqueada.');
    }

    public function print(Request $request, ClinicalPrescription $prescription, AccessScopeService $access)
    {
        $prescription->load([
            'patient',
            'appointment',
            'professional',
            'items',
            'document.signatures.user',
        ]);

        $this->authorizeScope($request, $prescription, $access);

        return view('clinical-prescriptions.print', compact('prescription'));
    }

    private function authorizeScope(
        Request $request,
        ClinicalPrescription $prescription,
        AccessScopeService $access
    ): void {
        abort_unless($prescription->patient, 404);
        abort_unless($access->canViewPatient($request->user(), $prescription->patient), 403);
        abort_unless(
            $access->canUseProfessional($request->user(), (int) $prescription->professional_id),
            403
        );

        if ($prescription->appointment) {
            abort_unless(
                $access->canViewAppointment($request->user(), $prescription->appointment),
                403
            );
        }
    }

    private function ensureEditable(ClinicalPrescription $prescription): void
    {
        if ($prescription->isLocked()) {
            throw ValidationException::withMessages([
                'prescription' => 'Prescrição assinada ou emitida não pode ser alterada.',
            ]);
        }
    }

    private function validateItem(Request $request): array
    {
        return $request->validate([
            'medication_name' => ['required', 'string', 'max:180'],
            'concentration' => ['nullable', 'string', 'max:120'],
            'dosage_form' => ['nullable', 'string', 'max:120'],
            'route' => ['nullable', 'string', 'max:120'],
            'quantity' => ['nullable', 'string', 'max:120'],
            'directions' => ['required', 'string', 'max:4000'],
            'duration' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function renderContent(ClinicalPrescription $prescription): string
    {
        $lines = [];

        foreach ($prescription->items as $index => $item) {
            $name = trim(implode(' ', array_filter([
                $item->medication_name,
                $item->concentration,
                $item->dosage_form,
            ])));

            $lines[] = ($index + 1).'. '.$name;

            if (filled($item->quantity)) {
                $lines[] = 'Quantidade: '.$item->quantity;
            }

            if (filled($item->route)) {
                $lines[] = 'Via: '.$item->route;
            }

            $lines[] = 'Posologia: '.$item->directions;

            if (filled($item->duration)) {
                $lines[] = 'Duração: '.$item->duration;
            }

            if (filled($item->notes)) {
                $lines[] = 'Observações: '.$item->notes;
            }

            $lines[] = '';
        }

        if (filled($prescription->notes)) {
            $lines[] = 'Orientações gerais:';
            $lines[] = $prescription->notes;
        }

        return trim(implode("\n", $lines));
    }
}
