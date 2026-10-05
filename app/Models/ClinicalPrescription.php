<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalPrescription extends Model
{
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'professional_id',
        'clinical_document_id',
        'prescription_type',
        'title',
        'notes',
        'status',
        'version',
        'issued_at',
        'signed_at',
        'content_hash',
        'external_provider',
        'external_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ClinicalDocument::class, 'clinical_document_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClinicalPrescriptionItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['signed', 'issued', 'cancelled'], true);
    }
}
