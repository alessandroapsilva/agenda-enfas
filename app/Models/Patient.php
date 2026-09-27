<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'name',
        'preferred_name',
        'phone',
        'secondary_phone',
        'email',
        'preferred_contact_channel',
        'contact_consent',
        'contact_consent_at',
        'contact_consent_source',
        'do_not_contact',
        'cpf',
        'rgea_number',
        'birth_date',
        'address_line',
        'address_number',
        'address_complement',
        'neighborhood',
        'city',
        'state',
        'postal_code',
        'notes',
        'tags',
        'last_contact_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'contact_consent' => 'boolean',
            'contact_consent_at' => 'datetime',
            'do_not_contact' => 'boolean',
            'last_contact_at' => 'datetime',
            'tags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WaMessage::class, 'patient_id');
    }

    public function displayName(): string
    {
        return $this->preferred_name ?: $this->name;
    }
}
