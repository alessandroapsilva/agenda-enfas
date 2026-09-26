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
        'mode',
        'unread_count',
        'context',
        'assigned_user_id',
        'human_taken_at',
        'last_message_at',
        'last_inbound_at',
        'last_outbound_at',
        'expires_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'last_message_at' => 'datetime',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
            'human_taken_at' => 'datetime',
            'expires_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
