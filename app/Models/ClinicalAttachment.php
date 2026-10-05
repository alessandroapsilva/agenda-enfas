<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalAttachment extends Model
{
    protected $fillable = [
        'patient_id','appointment_id','clinical_record_id','clinical_document_id',
        'category','title','original_name','disk','path','mime_type','size_bytes',
        'sha256','source','ocr_text','created_by',
    ];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function record(): BelongsTo { return $this->belongsTo(AppointmentClinicalRecord::class, 'clinical_record_id'); }
    public function document(): BelongsTo { return $this->belongsTo(ClinicalDocument::class, 'clinical_document_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
