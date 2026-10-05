<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientClinicalHistory extends Model
{
    protected $fillable = [
        'patient_id',
        'chronic_conditions',
        'surgeries',
        'family_history',
        'social_history',
        'immunizations',
        'other_history',
        'updated_by',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
