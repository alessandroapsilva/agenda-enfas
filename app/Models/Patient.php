<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function clinicalHistory(): HasOne
    {
        return $this->hasOne(PatientClinicalHistory::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    public function problems(): HasMany
    {
        return $this->hasMany(PatientProblem::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(PatientMedication::class);
    }

    public function clinicalEvents(): HasMany
    {
        return $this->hasMany(PatientClinicalEvent::class);
    }

    public function displayName(): string
    {
        return $this->preferred_name ?: $this->name;
    }
}
