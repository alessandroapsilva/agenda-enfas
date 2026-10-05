<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientProblem extends Model
{
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'code_system',
        'code',
        'description',
        'status',
        'onset_date',
        'resolved_date',
        'notes',
        'recorded_by',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'onset_date' => 'date',
            'resolved_date' => 'date',
        ];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
