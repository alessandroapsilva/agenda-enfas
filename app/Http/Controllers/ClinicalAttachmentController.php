<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ClinicalAttachment;
use App\Models\Patient;
use App\Services\Enfas\AccessScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClinicalAttachmentController extends Controller
{
    public function store(Request $request, AccessScopeService $access)
    {
        $data = $request->validate([
            'patient_id' => ['required','exists:patients,id'],
            'appointment_id' => ['nullable','exists:appointments,id'],
            'clinical_record_id' => ['nullable','exists:appointment_clinical_records,id'],
            'clinical_document_id' => ['nullable','exists:clinical_documents,id'],
            'category' => ['required', Rule::in([
                'prescription','exam','report','referral','identity','consent',
                'authorization','image','other',
            ])],
            'title' => ['required','string','max:180'],
            'source' => ['nullable', Rule::in(['upload','scanner','camera'])],
            'file' => ['required','file','mimes:pdf,jpg,jpeg,png,tif,tiff','max:30720'],
        ]);

        $patient = Patient::findOrFail((int) $data['patient_id']);
        abort_unless($access->canViewPatient($request->user(), $patient), 403);

        if (! empty($data['appointment_id'])) {
            $appointment = Appointment::findOrFail((int) $data['appointment_id']);
            abort_unless($access->canViewAppointment($request->user(), $appointment), 403);
            abort_unless((int) $appointment->patient_id === (int) $patient->id, 422);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $path = 'clinical/'.$patient->id.'/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;
        $bytes = $file->get();

        Storage::disk('local')->put($path, $bytes);

        $attachment = ClinicalAttachment::create([
            'patient_id' => $patient->id,
            'appointment_id' => $data['appointment_id'] ?? null,
            'clinical_record_id' => $data['clinical_record_id'] ?? null,
            'clinical_document_id' => $data['clinical_document_id'] ?? null,
            'category' => $data['category'],
            'title' => trim($data['title']),
            'original_name' => $file->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => strlen($bytes),
            'sha256' => hash('sha256', $bytes),
            'source' => $data['source'] ?? 'upload',
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Documento anexado com segurança.');
    }

    public function download(Request $request, ClinicalAttachment $attachment, AccessScopeService $access)
    {
        $attachment->load(['patient','appointment']);

        abort_unless($access->canViewPatient($request->user(), $attachment->patient), 403);

        if ($attachment->appointment) {
            abort_unless($access->canViewAppointment($request->user(), $attachment->appointment), 403);
        }

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream']
        );
    }
}
