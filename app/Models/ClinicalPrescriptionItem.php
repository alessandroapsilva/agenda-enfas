<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalPrescriptionItem extends Model
{
    protected $fillable = [
        'clinical_prescription_id',
        'sort_order',
        'medication_name',
        'concentration',
        'dosage_form',
        'route',
        'quantity',
        'directions',
        'duration',
        'notes',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(ClinicalPrescription::class, 'clinical_prescription_id');
    }
}
