<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentSeries extends Model
{
    protected $fillable = [
        'patient_id',
        'professional_id',
        'service_id',
        'frequency',
        'interval',
        'week_days',
        'starts_on',
        'ends_on',
        'max_occurrences',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'week_days' => 'array',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'metadata' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'series_id');
    }
}
