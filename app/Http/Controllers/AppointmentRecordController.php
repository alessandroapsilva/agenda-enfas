<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentClinicalAddendum;
use App\Models\AppointmentClinicalRecord;
use App\Models\AppointmentClinicalScale;
use App\Models\ClinicalAttachment;
use App\Models\ClinicalCarePlan;
use App\Models\PatientAllergy;
use App\Models\PatientClinicalHistory;
use App\Models\PatientMedication;
use App\Models\PatientProblem;
use App\Models\AppointmentEvent;
use App\Services\Clinical\ClinicalTimelineService;
use App\Services\Enfas\AccessScopeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentRecordController extends Controller
{
    public function show(
        Request $request,
        Appointment $appointment,
        AccessScopeService $access,
        ClinicalTimelineService $timelineService
    ) {
        abort_unless($access->canViewAppointment($request->user(), $appointment), 403);

        $appointment->load([
            'patient',
            'professional',
            'service',
            'location',
            'clinicalRecord.addenda.author',
            'clinicalRecord.creator',
            'clinicalRecord.updater',
            'clinicalRecord.finalizer',
        ]);

        $record = $appointment->clinicalRecord;

        $attachments = ClinicalAttachment::query()
            ->where('patient_id', $appointment->patient_id)
            ->where(function ($query) use ($appointment) {
                $query->where('appointment_id', $appointment->id)
                    ->orWhereNull('appointment_id');
            })
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $clinicalHistory = PatientClinicalHistory::firstOrNew([
            'patient_id' => $appointment->patient_id,
        ]);

        $allergies = PatientAllergy::query()
            ->where('patient_id', $appointment->patient_id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('recorded_at')
            ->limit(30)
            ->get();

        $problems = PatientProblem::query()
            ->where('patient_id', $appointment->patient_id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        $medications = PatientMedication::query()
            ->where('patient_id', $appointment->patient_id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        $scales = AppointmentClinicalScale::query()
            ->where('appointment_id', $appointment->id)
            ->orderByDesc('recorded_at')
            ->limit(30)
            ->get();

        $carePlans = ClinicalCarePlan::query()
            ->with('responsibleProfessional')
            ->where('appointment_id', $appointment->id)
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 WHEN 'planned' THEN 1 WHEN 'completed' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->get();

        $timeline = $timelineService->forPatient(
            $appointment->patient,
            $request->user()
        );

        return view('appointments.record', compact(
            'appointment',
            'record',
            'attachments',
            'clinicalHistory',
            'allergies',
            'problems',
            'medications',
            'scales',
            'carePlans',
            'timeline'
        ));
    }

    public function save(Request $request, Appointment $appointment, AccessScopeService $access)
    {
        abort_unless($access->canViewAppointment($request->user(), $appointment), 403);

        $record = AppointmentClinicalRecord::firstOrNew([
            'appointment_id' => $appointment->id,
        ]);

        if ($record->exists && $record->isFinalized()) {
            throw ValidationException::withMessages([
                'record' => 'Registro finalizado. Use uma complementação.',
            ]);
        }

        $data = $request->validate([
            'reason_for_visit' => ['nullable','string','max:8000'],
            'history' => ['nullable','string','max:12000'],
            'physical_exam' => ['nullable','string','max:12000'],
            'assessment' => ['nullable','string','max:12000'],
            'clinical_impression' => ['nullable','string','max:12000'],
            'interventions' => ['nullable','string','max:12000'],
            'guidance' => ['nullable','string','max:12000'],
            'evolution' => ['nullable','string','max:12000'],
            'follow_up_plan' => ['nullable','string','max:8000'],
            'care_plan_summary' => ['nullable','string','max:8000'],
            'vitals' => ['nullable','array'],
            'vitals.systolic_bp' => ['nullable','integer','between:40,300'],
            'vitals.diastolic_bp' => ['nullable','integer','between:20,200'],
            'vitals.heart_rate' => ['nullable','integer','between:20,250'],
            'vitals.respiratory_rate' => ['nullable','integer','between:5,80'],
            'vitals.spo2' => ['nullable','numeric','between:40,100'],
            'vitals.temperature' => ['nullable','numeric','between:30,45'],
            'vitals.weight' => ['nullable','numeric','between:0.5,500'],
            'vitals.height' => ['nullable','numeric','between:20,250'],
            'vitals.glucose' => ['nullable','numeric','between:10,1000'],
            'vitals.pain_score' => ['nullable','numeric','between:0,10'],
        ]);

        $vitals = collect($data['vitals'] ?? [])
            ->reject(fn ($value) => $value === null || $value === '')
            ->all();

        $data['vitals'] = $vitals === [] ? null : $vitals;

        $record->fill($data);
        $record->patient_id = $appointment->patient_id;
        $record->professional_id = $appointment->professional_id;
        $record->status = 'draft';
        $record->started_at = $record->started_at ?: now();
        $record->created_by = $record->created_by ?: $request->user()->id;
        $record->updated_by = $request->user()->id;
        $record->save();

        AppointmentEvent::create([
            'appointment_id' => $appointment->id,
            'user_id' => $request->user()->id,
            'event_type' => 'record_saved',
            'title' => 'Prontuário salvo',
            'description' => 'Rascunho do atendimento atualizado.',
            'occurred_at' => now(),
        ]);

        return back()->with('success', 'Prontuário salvo como rascunho.');
    }

    public function finalize(Request $request, Appointment $appointment, AccessScopeService $access)
    {
        abort_unless($access->canViewAppointment($request->user(), $appointment), 403);

        $record = AppointmentClinicalRecord::where('appointment_id', $appointment->id)->firstOrFail();

        if ($record->isFinalized()) {
            return back()->with('success', 'O prontuário já estava finalizado.');
        }

        $hasContent = collect([
            $record->reason_for_visit,
            $record->history,
            $record->physical_exam,
            $record->assessment,
            $record->clinical_impression,
            $record->interventions,
            $record->guidance,
            $record->evolution,
            $record->follow_up_plan,
            $record->care_plan_summary,
        ])->contains(fn ($value) => filled($value)) || ! empty($record->vitals);

        if (! $hasContent) {
            throw ValidationException::withMessages([
                'record' => 'Preencha pelo menos um campo antes de finalizar.',
            ]);
        }

        $record->status = 'finalized';
        $record->finalized_at = now();
        $record->finalized_by = $request->user()->id;
        $record->updated_by = $request->user()->id;
        $record->integrity_hash = hash('sha256', json_encode([
            'appointment_id' => $record->appointment_id,
            'patient_id' => $record->patient_id,
            'professional_id' => $record->professional_id,
            'reason_for_visit' => $record->reason_for_visit,
            'history' => $record->history,
            'physical_exam' => $record->physical_exam,
            'vitals' => $record->vitals,
            'assessment' => $record->assessment,
            'clinical_impression' => $record->clinical_impression,
            'interventions' => $record->interventions,
            'guidance' => $record->guidance,
            'evolution' => $record->evolution,
            'follow_up_plan' => $record->follow_up_plan,
            'care_plan_summary' => $record->care_plan_summary,
            'finalized_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $record->save();

        AppointmentEvent::create([
            'appointment_id' => $appointment->id,
            'user_id' => $request->user()->id,
            'event_type' => 'record_finalized',
            'title' => 'Prontuário finalizado',
            'description' => 'Registro bloqueado para edição.',
            'metadata' => [
                'record_id' => $record->id,
                'integrity_hash' => $record->integrity_hash,
            ],
            'occurred_at' => now(),
        ]);

        return back()->with('success', 'Prontuário finalizado.');
    }

    public function addendum(Request $request, Appointment $appointment, AccessScopeService $access)
    {
        abort_unless($access->canViewAppointment($request->user(), $appointment), 403);

        $record = AppointmentClinicalRecord::where('appointment_id', $appointment->id)->firstOrFail();
        abort_unless($record->isFinalized(), 422);

        $data = $request->validate([
            'body' => ['required','string','min:3','max:8000'],
        ]);

        $addendum = AppointmentClinicalAddendum::create([
            'clinical_record_id' => $record->id,
            'body' => trim($data['body']),
            'created_by' => $request->user()->id,
            'signed_at' => now(),
        ]);

        AppointmentEvent::create([
            'appointment_id' => $appointment->id,
            'user_id' => $request->user()->id,
            'event_type' => 'record_addendum',
            'title' => 'Complementação do prontuário',
            'description' => 'Complementação adicionada ao registro finalizado.',
            'metadata' => ['addendum_id' => $addendum->id],
            'occurred_at' => now(),
        ]);

        return back()->with('success', 'Complementação adicionada.');
    }

}
