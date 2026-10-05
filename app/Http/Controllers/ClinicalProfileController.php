<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentClinicalRecord;
use App\Models\AppointmentClinicalScale;
use App\Models\ClinicalCarePlan;
use App\Models\PatientAllergy;
use App\Models\PatientClinicalEvent;
use App\Models\PatientClinicalHistory;
use App\Models\PatientMedication;
use App\Models\PatientProblem;
use App\Services\Enfas\AccessScopeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClinicalProfileController extends Controller
{
    public function updateHistory(
        Request $request,
        Appointment $appointment,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);

        $data = $request->validate([
            'chronic_conditions' => ['nullable', 'string', 'max:12000'],
            'surgeries' => ['nullable', 'string', 'max:12000'],
            'family_history' => ['nullable', 'string', 'max:12000'],
            'social_history' => ['nullable', 'string', 'max:12000'],
            'immunizations' => ['nullable', 'string', 'max:12000'],
            'other_history' => ['nullable', 'string', 'max:12000'],
        ]);

        PatientClinicalHistory::updateOrCreate(
            ['patient_id' => $appointment->patient_id],
            [...$data, 'updated_by' => $request->user()->id]
        );

        $this->logEvent(
            $request,
            $appointment,
            'history_updated',
            'Antecedentes atualizados',
            'Histórico clínico longitudinal revisado.'
        );

        return back()->with('success', 'Antecedentes clínicos atualizados.');
    }

    public function storeAllergy(
        Request $request,
        Appointment $appointment,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);

        $data = $request->validate([
            'substance' => ['required', 'string', 'max:180'],
            'reaction' => ['nullable', 'string', 'max:2000'],
            'severity' => ['required', Rule::in(['unknown', 'mild', 'moderate', 'severe'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $allergy = PatientAllergy::create([
            ...$data,
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'status' => 'active',
            'recorded_at' => now(),
            'recorded_by' => $request->user()->id,
        ]);

        $this->logEvent(
            $request,
            $appointment,
            'allergy_recorded',
            'Alergia registrada',
            $allergy->substance,
            ['allergy_id' => $allergy->id, 'severity' => $allergy->severity]
        );

        return back()->with('success', 'Alergia registrada no perfil clínico.');
    }

    public function resolveAllergy(
        Request $request,
        Appointment $appointment,
        PatientAllergy $allergy,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);
        abort_unless((int) $allergy->patient_id === (int) $appointment->patient_id, 404);

        $allergy->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
        ]);

        $this->logEvent(
            $request,
            $appointment,
            'allergy_resolved',
            'Alergia marcada como resolvida',
            $allergy->substance,
            ['allergy_id' => $allergy->id]
        );

        return back()->with('success', 'Alergia marcada como resolvida.');
    }

    public function storeProblem(
        Request $request,
        Appointment $appointment,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);

        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'code_system' => ['nullable', 'string', 'max:30'],
            'code' => ['nullable', 'string', 'max:40'],
            'onset_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $problem = PatientProblem::create([
            ...$data,
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'status' => 'active',
            'recorded_by' => $request->user()->id,
        ]);

        $this->logEvent(
            $request,
            $appointment,
            'problem_recorded',
            'Problema clínico registrado',
            $problem->description,
            ['problem_id' => $problem->id, 'code' => $problem->code]
        );

        return back()->with('success', 'Problema clínico incluído na lista longitudinal.');
    }

    public function resolveProblem(
        Request $request,
        Appointment $appointment,
        PatientProblem $problem,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);
        abort_unless((int) $problem->patient_id === (int) $appointment->patient_id, 404);

        $problem->update([
            'status' => 'resolved',
            'resolved_date' => now()->toDateString(),
            'resolved_by' => $request->user()->id,
        ]);

        $this->logEvent(
            $request,
            $appointment,
            'problem_resolved',
            'Problema clínico resolvido',
            $problem->description,
            ['problem_id' => $problem->id]
        );

        return back()->with('success', 'Problema marcado como resolvido.');
    }

    public function storeMedication(
        Request $request,
        Appointment $appointment,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);

        $data = $request->validate([
            'medication_name' => ['required', 'string', 'max:180'],
            'concentration' => ['nullable', 'string', 'max:120'],
            'route' => ['nullable', 'string', 'max:120'],
            'directions' => ['nullable', 'string', 'max:4000'],
            'started_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $medication = PatientMedication::create([
            ...$data,
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'status' => 'active',
            'recorded_by' => $request->user()->id,
        ]);

        $this->logEvent(
            $request,
            $appointment,
            'medication_recorded',
            'Medicamento em uso registrado',
            trim($medication->medication_name.' '.$medication->concentration),
            ['medication_id' => $medication->id]
        );

        return back()->with('success', 'Medicamento incluído no perfil clínico.');
    }

    public function stopMedication(
        Request $request,
        Appointment $appointment,
        PatientMedication $medication,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);
        abort_unless((int) $medication->patient_id === (int) $appointment->patient_id, 404);

        $data = $request->validate([
            'stop_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $medication->update([
            'status' => 'stopped',
            'stopped_on' => now()->toDateString(),
            'stop_reason' => filled($data['stop_reason'] ?? null) ? trim($data['stop_reason']) : null,
            'stopped_by' => $request->user()->id,
        ]);

        $this->logEvent(
            $request,
            $appointment,
            'medication_stopped',
            'Medicamento marcado como suspenso',
            $medication->medication_name,
            ['medication_id' => $medication->id]
        );

        return back()->with('success', 'Medicamento marcado como suspenso.');
    }

    public function storeScale(
        Request $request,
        Appointment $appointment,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);

        $data = $request->validate([
            'scale_name' => ['required', 'string', 'max:160'],
            'scale_key' => ['nullable', 'string', 'max:80'],
            'score' => ['nullable', 'numeric', 'between:-9999,9999'],
            'classification' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $record = AppointmentClinicalRecord::where('appointment_id', $appointment->id)->first();

        $scale = AppointmentClinicalScale::create([
            ...$data,
            'appointment_id' => $appointment->id,
            'clinical_record_id' => $record?->id,
            'patient_id' => $appointment->patient_id,
            'professional_id' => $appointment->professional_id,
            'recorded_by' => $request->user()->id,
            'recorded_at' => now(),
        ]);

        $description = $scale->scale_name;
        if ($scale->score !== null) {
            $description .= ' · escore '.$scale->score;
        }
        if (filled($scale->classification)) {
            $description .= ' · '.$scale->classification;
        }

        $this->logEvent(
            $request,
            $appointment,
            'clinical_scale_recorded',
            'Escala clínica registrada',
            $description,
            ['scale_id' => $scale->id]
        );

        return back()->with('success', 'Escala clínica registrada.');
    }

    public function storeCarePlan(
        Request $request,
        Appointment $appointment,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);

        $data = $request->validate([
            'goal' => ['required', 'string', 'max:500'],
            'actions' => ['required', 'string', 'max:8000'],
            'target_date' => ['nullable', 'date'],
            'responsible_professional_id' => ['nullable', 'exists:professionals,id'],
        ]);

        $responsible = (int) ($data['responsible_professional_id'] ?? $appointment->professional_id);
        abort_unless($access->canUseProfessional($request->user(), $responsible), 403);

        $record = AppointmentClinicalRecord::where('appointment_id', $appointment->id)->first();

        $plan = ClinicalCarePlan::create([
            ...$data,
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'clinical_record_id' => $record?->id,
            'responsible_professional_id' => $responsible,
            'status' => 'planned',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->logEvent(
            $request,
            $appointment,
            'care_plan_created',
            'Meta do plano terapêutico criada',
            $plan->goal,
            ['care_plan_id' => $plan->id]
        );

        return back()->with('success', 'Meta incluída no plano terapêutico.');
    }

    public function updateCarePlanStatus(
        Request $request,
        Appointment $appointment,
        ClinicalCarePlan $plan,
        AccessScopeService $access
    ) {
        $this->authorizeAppointment($request, $appointment, $access);
        abort_unless((int) $plan->appointment_id === (int) $appointment->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])],
        ]);

        $plan->update([
            'status' => $data['status'],
            'completed_at' => $data['status'] === 'completed' ? now() : null,
            'updated_by' => $request->user()->id,
        ]);

        $this->logEvent(
            $request,
            $appointment,
            'care_plan_status_changed',
            'Plano terapêutico atualizado',
            $plan->goal.' · '.$data['status'],
            ['care_plan_id' => $plan->id, 'status' => $data['status']]
        );

        return back()->with('success', 'Status do plano terapêutico atualizado.');
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
