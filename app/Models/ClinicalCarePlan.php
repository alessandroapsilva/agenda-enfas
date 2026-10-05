<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalCarePlan extends Model
{
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'clinical_record_id',
        'responsible_professional_id',
        'goal',
        'actions',
        'target_date',
        'status',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function record(): BelongsTo { return $this->belongsTo(AppointmentClinicalRecord::class, 'clinical_record_id'); }
    public function responsibleProfessional(): BelongsTo { return $this->belongsTo(Professional::class, 'responsible_professional_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
