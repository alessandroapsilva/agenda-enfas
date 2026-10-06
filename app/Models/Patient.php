<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasUlids;

    protected $fillable = [
        'organization_id','medical_record_number','name','social_name','cpf','cns',
        'birth_date','sex','phone','email','mother_name','metadata',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'metadata' => 'array'];
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
