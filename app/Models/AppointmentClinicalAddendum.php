<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentClinicalAddendum extends Model
{
    protected $table = 'appointment_clinical_addenda';

    protected $fillable = [
        'clinical_record_id',
        'body',
        'created_by',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(AppointmentClinicalRecord::class, 'clinical_record_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
