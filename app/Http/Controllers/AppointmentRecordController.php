<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentClinicalAddendum;
use App\Models\AppointmentClinicalRecord;
use App\Models\AppointmentEvent;
use App\Services\Enfas\AccessScopeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentRecordController extends Controller
{
    public function show(Request $request, Appointment $appointment, AccessScopeService $access)
    {
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

        return view('appointments.record', compact('appointment', 'record'));
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
            'assessment' => ['nullable','string','max:12000'],
            'interventions' => ['nullable','string','max:12000'],
            'guidance' => ['nullable','string','max:12000'],
            'evolution' => ['nullable','string','max:12000'],
            'follow_up_plan' => ['nullable','string','max:8000'],
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
}
