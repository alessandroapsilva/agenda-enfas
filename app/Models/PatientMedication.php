<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedication extends Model
{
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'medication_name',
        'concentration',
        'route',
        'directions',
        'started_on',
        'stopped_on',
        'status',
        'stop_reason',
        'notes',
        'recorded_by',
        'stopped_by',
    ];

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'stopped_on' => 'date',
        ];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
