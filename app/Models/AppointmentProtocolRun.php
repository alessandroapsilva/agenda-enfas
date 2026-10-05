<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentProtocolRun extends Model
{
    protected $fillable = [
        'appointment_id',
        'patient_id',
        'professional_id',
        'template_id',
        'status',
        'started_at',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function template(): BelongsTo { return $this->belongsTo(ClinicalProtocolTemplate::class, 'template_id'); }
    public function responses(): HasMany { return $this->hasMany(AppointmentProtocolResponse::class, 'protocol_run_id'); }
}
