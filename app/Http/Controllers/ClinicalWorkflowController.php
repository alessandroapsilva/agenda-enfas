<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentClinicalEvolution;
use App\Models\AppointmentProtocolResponse;
use App\Models\AppointmentProtocolRun;
use App\Models\ClinicalProtocolTemplate;
use App\Models\PatientClinicalEvent;
use App\Services\Enfas\AccessScopeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClinicalWorkflowController extends Controller
{
    public function storeEvolution(
        Request $request,
        Appointment $appointment,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);

        $data = $request->validate([
            'format' => ['required', Rule::in(['soap', 'narrative'])],
            'subjective' => ['nullable', 'string', 'max:12000'],
            'objective' => ['nullable', 'string', 'max:12000'],
            'assessment' => ['nullable', 'string', 'max:12000'],
            'plan' => ['nullable', 'string', 'max:12000'],
            'body' => ['nullable', 'string', 'max:20000'],
        ]);

        if ($data['format'] === 'soap') {
            $hasSoap = collect([
                $data['subjective'] ?? null,
                $data['objective'] ?? null,
                $data['assessment'] ?? null,
                $data['plan'] ?? null,
            ])->contains(fn ($value) => filled($value));

            if (! $hasSoap) {
                throw ValidationException::withMessages([
                    'evolution' => 'Preencha pelo menos um campo da evolução SOAP.',
                ]);
            }
        }

        if ($data['format'] === 'narrative' && blank($data['body'] ?? null)) {
            throw ValidationException::withMessages([
                'body' => 'Preencha o texto da evolução narrativa.',
            ]);
        }

        $signedAt = now();

        $payload = [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'professional_id' => $appointment->professional_id,
            'format' => $data['format'],
            'subjective' => $data['subjective'] ?? null,
            'objective' => $data['objective'] ?? null,
            'assessment' => $data['assessment'] ?? null,
            'plan' => $data['plan'] ?? null,
            'body' => $data['body'] ?? null,
            'signed_at' => $signedAt->toIso8601String(),
        ];

        $evolution = AppointmentClinicalEvolution::create([
            ...$payload,
            'integrity_hash' => hash(
                'sha256',
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ),
            'created_by' => $request->user()->id,
            'signed_at' => $signedAt,
        ]);

        $this->logEvent(
            $request,
            $appointment,
            'structured_evolution_signed',
            'Evolução clínica assinada',
            $data['format'] === 'soap' ? 'Evolução SOAP registrada.' : 'Evolução narrativa registrada.',
            ['evolution_id' => $evolution->id, 'integrity_hash' => $evolution->integrity_hash]
        );

        return back()->with('success', 'Evolução registrada e bloqueada com hash de integridade.');
    }

    public function startProtocol(
        Request $request,
        Appointment $appointment,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);

        $data = $request->validate([
            'template_id' => ['required', 'exists:clinical_protocol_templates,id'],
        ]);

        $template = ClinicalProtocolTemplate::query()
            ->where('is_active', true)
            ->findOrFail((int) $data['template_id']);

        $run = AppointmentProtocolRun::firstOrCreate(
            [
                'appointment_id' => $appointment->id,
                'template_id' => $template->id,
                'status' => 'in_progress',
            ],
            [
                'patient_id' => $appointment->patient_id,
                'professional_id' => $appointment->professional_id,
                'started_at' => now(),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]
        );

        $this->logEvent(
            $request,
            $appointment,
            'protocol_started',
            'Protocolo iniciado',
            $template->name,
            ['protocol_run_id' => $run->id, 'template_id' => $template->id]
        );

        return back()->with('success', 'Protocolo iniciado.');
    }

    public function updateProtocol(
        Request $request,
        Appointment $appointment,
        AppointmentProtocolRun $run,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);
        abort_unless((int) $run->appointment_id === (int) $appointment->id, 404);

        if ($run->status === 'completed') {
            throw ValidationException::withMessages([
                'protocol' => 'Protocolo concluído não pode ser alterado.',
            ]);
        }

        $data = $request->validate([
            'responses' => ['nullable', 'array'],
            'responses.*.value' => ['nullable', Rule::in(['done', 'not_done', 'not_applicable'])],
            'responses.*.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $run->load('template.items');
        $validItemIds = $run->template->items->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($data['responses'] ?? [] as $itemId => $response) {
            $itemId = (int) $itemId;

            if (! in_array($itemId, $validItemIds, true)) {
                continue;
            }

            if (blank($response['value'] ?? null) && blank($response['notes'] ?? null)) {
                continue;
            }

            AppointmentProtocolResponse::updateOrCreate(
                [
                    'protocol_run_id' => $run->id,
                    'template_item_id' => $itemId,
                ],
                [
                    'value' => $response['value'] ?? 'not_done',
                    'notes' => filled($response['notes'] ?? null) ? trim($response['notes']) : null,
                    'recorded_by' => $request->user()->id,
                ]
            );
        }

        $run->update(['updated_by' => $request->user()->id]);

        return back()->with('success', 'Checklist atualizado.');
    }

    public function completeProtocol(
        Request $request,
        Appointment $appointment,
        AppointmentProtocolRun $run,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);
        abort_unless((int) $run->appointment_id === (int) $appointment->id, 404);

        if ($run->status === 'completed') {
            return back()->with('success', 'O protocolo já estava concluído.');
        }

        $run->load(['template.items', 'responses']);
        $answered = $run->responses->pluck('template_item_id')->map(fn ($id) => (int) $id)->all();

        $missingRequired = $run->template->items
            ->where('is_required', true)
            ->reject(fn ($item) => in_array((int) $item->id, $answered, true));

        if ($missingRequired->isNotEmpty()) {
            throw ValidationException::withMessages([
                'protocol' => 'Responda todos os itens obrigatórios antes de concluir o protocolo.',
            ]);
        }

        $run->update([
            'status' => 'completed',
            'completed_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        $this->logEvent(
            $request,
            $appointment,
            'protocol_completed',
            'Protocolo concluído',
            $run->template->name,
            ['protocol_run_id' => $run->id, 'template_id' => $run->template_id]
        );

        return back()->with('success', 'Protocolo concluído e registrado na timeline.');
    }

    private function authorizeAppointment(
        Request $request,
        Appointment $appointment,
        AccessScopeService $access
    ): void {
        abort_unless($access->canViewAppointment($request->user(), $appointment), 403);
    }

    private function logEvent(
        Request $request,
        Appointment $appointment,
        string $eventType,
        string $title,
        ?string $description = null,
        array $metadata = []
    ): void {
        PatientClinicalEvent::create([
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'user_id' => $request->user()->id,
            'event_type' => $eventType,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }
}
