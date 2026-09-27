<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name',
        'code',
        'phone',
        'whatsapp',
        'email',
        'address',
        'address_number',
        'address_complement',
        'neighborhood',
        'city',
        'state',
        'postal_code',
        'responsible_name',
        'opening_hours',
        'patient_instructions',
        'notes',
        'is_main',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function fullAddress(): string
    {
        return collect([
            trim(($this->address ?: '').($this->address_number ? ', '.$this->address_number : '')),
            $this->address_complement,
            $this->neighborhood,
            collect([$this->city, $this->state])->filter()->implode('/'),
            $this->postal_code,
        ])->filter()->implode(' · ');
    }
}
