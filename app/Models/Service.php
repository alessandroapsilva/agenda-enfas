<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'name',
        'code',
        'duration_minutes',
        'description',
        'arrival_minutes',
        'required_documents',
        'preparation_instructions',
        'aftercare_instructions',
        'return_after_days',
        'allow_online_reschedule',
        'allow_recurrence',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allow_online_reschedule' => 'boolean',
            'allow_recurrence' => 'boolean',
        ];
    }

    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
