<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentClinicalRecord extends Model
{
    protected $fillable = [
        'appointment_id',
        'patient_id',
        'professional_id',
        'status',
        'version',
        'reason_for_visit',
        'history',
        'vitals',
        'assessment',
        'interventions',
        'guidance',
        'evolution',
        'follow_up_plan',
        'started_at',
        'finalized_at',
        'finalized_by',
        'integrity_hash',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'vitals' => 'array',
            'started_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function addenda(): HasMany
    {
        return $this->hasMany(AppointmentClinicalAddendum::class, 'clinical_record_id')
            ->orderByDesc('signed_at');
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }
}
