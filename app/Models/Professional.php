<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Professional extends Model
{
    protected $fillable = [
        'name',
        'specialty',
        'phone',
        'email',
        'work_start',
        'work_end',
        'active_days',
        'slot_interval',
        'color',
        'is_active',
        'whatsapp_notifications_enabled',
    ];

    protected function casts(): array
    {
        return [
            'active_days' => 'array',
            'is_active' => 'boolean',
            'whatsapp_notifications_enabled' => 'boolean',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(ProfessionalAvailability::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
