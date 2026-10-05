<?php

namespace App\Services\Clinical;

use App\Models\AppointmentEvent;
use App\Models\ClinicalAttachment;
use App\Models\ClinicalDocument;
use App\Models\ClinicalPrescription;
use App\Models\Patient;
use App\Models\PatientClinicalEvent;
use App\Models\User;
use Illuminate\Support\Collection;

class ClinicalTimelineService
{
    public function forPatient(Patient $patient, User $user, int $limit = 80): Collection
    {
        $professionalId = $user->role === 'professional'
            ? (int) ($user->professional_id ?: 0)
            : null;

        $clinicalEvents = PatientClinicalEvent::query()
            ->with(['user:id,name', 'appointment:id,code'])
            ->where('patient_id', $patient->id)
            ->latest('occurred_at')
            ->limit(40)
            ->get()
            ->map(fn ($event) => [
                'occurred_at' => $event->occurred_at,
                'type' => $event->event_type,
                'icon' => 'bi bi-heart-pulse',
                'title' => $event->title,
                'description' => $event->description,
                'context' => $event->appointment?->code,
                'actor' => $event->user?->name,
                'link' => null,
            ]);

        $appointmentEvents = AppointmentEvent::query()
            ->with(['appointment:id,code,patient_id,professional_id', 'user:id,name'])
            ->whereHas('appointment', function ($query) use ($patient, $professionalId) {
                $query->where('patient_id', $patient->id);

                if ($professionalId !== null) {
                    $query->where('professional_id', $professionalId);
                }
            })
            ->latest('occurred_at')
            ->limit(40)
            ->get()
            ->map(fn ($event) => [
                'occurred_at' => $event->occurred_at,
                'type' => $event->event_type,
                'icon' => 'bi bi-journal-medical',
                'title' => $event->title,
                'description' => $event->description,
                'context' => $event->appointment?->code,
                'actor' => $event->user?->name,
                'link' => $event->appointment
                    ? route('appointments.record', $event->appointment_id)
                    : null,
            ]);

        $documents = ClinicalDocument::query()
            ->where('patient_id', $patient->id)
            ->when($professionalId !== null, fn ($query) => $query->where('professional_id', $professionalId))
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($document) => [
                'occurred_at' => $document->signed_at ?: $document->created_at,
                'type' => 'clinical_document',
                'icon' => 'bi bi-file-earmark-medical',
                'title' => $document->title,
                'description' => $document->status === 'signed'
                    ? 'Documento clínico assinado.'
                    : 'Documento clínico criado.',
                'context' => mb_strtoupper((string) $document->document_type),
                'actor' => null,
                'link' => route('clinical-documents.show', $document),
            ]);

        $prescriptions = ClinicalPrescription::query()
            ->where('patient_id', $patient->id)
            ->when($professionalId !== null, fn ($query) => $query->where('professional_id', $professionalId))
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($prescription) => [
                'occurred_at' => $prescription->signed_at ?: $prescription->created_at,
                'type' => 'prescription',
                'icon' => 'bi bi-capsule-pill',
                'title' => $prescription->title,
                'description' => $prescription->status === 'signed'
                    ? 'Prescrição assinada e emitida.'
                    : 'Prescrição criada como rascunho.',
                'context' => 'PRESCRIÇÃO',
                'actor' => null,
                'link' => route('clinical-prescriptions.show', $prescription),
            ]);

        $attachments = ClinicalAttachment::query()
            ->where('patient_id', $patient->id)
            ->when($professionalId !== null, function ($query) use ($professionalId) {
                $query->where(function ($scope) use ($professionalId) {
                    $scope->whereNull('appointment_id')
                        ->orWhereHas('appointment', fn ($appointment) => $appointment->where('professional_id', $professionalId));
                });
            })
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($attachment) => [
                'occurred_at' => $attachment->created_at,
                'type' => 'attachment',
                'icon' => 'bi bi-paperclip',
                'title' => $attachment->title,
                'description' => 'Documento anexado ao prontuário.',
                'context' => mb_strtoupper((string) $attachment->category),
                'actor' => null,
                'link' => route('clinical-attachments.download', $attachment),
            ]);

        return collect()
            ->concat($clinicalEvents)
            ->concat($appointmentEvents)
            ->concat($documents)
            ->concat($prescriptions)
            ->concat($attachments)
            ->filter(fn ($item) => $item['occurred_at'])
            ->sortByDesc(fn ($item) => $item['occurred_at']->getTimestamp())
            ->take($limit)
            ->values();
    }
}
