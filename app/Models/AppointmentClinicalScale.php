<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentClinicalScale extends Model
{
    protected $fillable = [
        'appointment_id',
        'clinical_record_id',
        'patient_id',
        'professional_id',
        'scale_key',
        'scale_name',
        'score',
        'classification',
        'payload',
        'notes',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'payload' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function record(): BelongsTo { return $this->belongsTo(AppointmentClinicalRecord::class, 'clinical_record_id'); }
    public function professional(): BelongsTo { return $this->belongsTo(Professional::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
