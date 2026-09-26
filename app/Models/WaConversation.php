<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaConversation extends Model
{
    protected $table = 'wa_conversations';

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'phone',
        'state',
        'status',
        'context',
        'assigned_user_id',
        'last_message_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'last_message_at' => 'datetime',
            'expires_at' => 'datetime',
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
}
