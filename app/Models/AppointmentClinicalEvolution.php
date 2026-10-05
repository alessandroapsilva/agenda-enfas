<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentClinicalEvolution extends Model
{
    protected $fillable = [
        'appointment_id',
        'patient_id',
        'professional_id',
        'format',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'body',
        'integrity_hash',
        'created_by',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function professional(): BelongsTo { return $this->belongsTo(Professional::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
