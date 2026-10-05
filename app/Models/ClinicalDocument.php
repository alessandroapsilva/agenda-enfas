<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalDocument extends Model
{
    protected $fillable = [
        'patient_id','appointment_id','professional_id','template_id',
        'document_type','title','content','status','version',
        'issued_at','signed_at','content_hash','external_provider',
        'external_id','created_by','updated_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function professional(): BelongsTo { return $this->belongsTo(Professional::class); }
    public function template(): BelongsTo { return $this->belongsTo(ClinicalDocumentTemplate::class); }
    public function signatures(): HasMany { return $this->hasMany(ClinicalDocumentSignature::class); }

    public function isLocked(): bool
    {
        return in_array($this->status, ['signed','issued'], true);
    }
}
