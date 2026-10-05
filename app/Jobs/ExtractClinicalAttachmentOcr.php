<?php

namespace App\Jobs;

use App\Models\ClinicalAttachment;
use App\Models\PatientClinicalEvent;
use App\Services\Clinical\ClinicalOcrService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ExtractClinicalAttachmentOcr implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 240;

    public function __construct(public int $attachmentId)
    {
        $this->onQueue('ocr');
    }

    public function handle(ClinicalOcrService $ocr): void
    {
        if (! config('clinical_ocr.enabled')) {
            return;
        }

        $attachment = ClinicalAttachment::find($this->attachmentId);

        if (! $attachment) {
            return;
        }

        $attachment->update([
            'ocr_status' => 'processing',
            'ocr_error' => null,
        ]);

        try {
            $text = $ocr->extract($attachment);

            $attachment->update([
                'ocr_text' => $text !== '' ? $text : null,
                'ocr_status' => $text !== '' ? 'completed' : 'empty',
                'ocr_error' => null,
                'ocr_processed_at' => now(),
            ]);

            PatientClinicalEvent::create([
                'patient_id' => $attachment->patient_id,
                'appointment_id' => $attachment->appointment_id,
                'user_id' => $attachment->created_by,
                'event_type' => 'attachment_ocr_completed',
                'title' => 'OCR do documento concluído',
                'description' => $attachment->title,
                'metadata' => [
                    'attachment_id' => $attachment->id,
                    'ocr_status' => $attachment->fresh()->ocr_status,
                ],
                'occurred_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $attachment->update([
                'ocr_status' => 'failed',
                'ocr_error' => mb_substr($exception->getMessage(), 0, 4000),
                'ocr_processed_at' => now(),
            ]);

            throw $exception;
        }
    }
}
