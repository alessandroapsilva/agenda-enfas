<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    protected $fillable = [
        'patient_id',
        'service_id',
        'professional_id',
        'location_id',
        'preferred_period',
        'earliest_date',
        'latest_date',
        'status',
        'offered_start_at',
        'offered_end_at',
        'offer_expires_at',
        'appointment_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'earliest_date' => 'date',
            'latest_date' => 'date',
            'offered_start_at' => 'datetime',
            'offered_end_at' => 'datetime',
            'offer_expires_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
