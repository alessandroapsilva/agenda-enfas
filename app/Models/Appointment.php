<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Appointment extends Model
{
    protected $fillable = [
        'code',
        'public_token',
        'patient_id',
        'professional_id',
        'service_id',
        'location_id',
        'series_id',
        'series_position',
        'rescheduled_from_id',
        'start_at',
        'end_at',
        'duration_minutes',
        'status',
        'confirmation_status',
        'confirmation_channel',
        'confirmation_requested_at',
        'confirmed_at',
        'cancelled_at',
        'completed_at',
        'rescheduled_at',
        'cancellation_reason',
        'source',
        'appointment_type',
        'medication_name',
        'medication_quantity',
        'medication_notes',
        'notes',
        'whatsapp_message_id',
        'check_in_completed_at',
        'satisfaction_score',
        'satisfaction_stars',
        'satisfaction_comment',
        'satisfaction_at',
        'return_due_at',
        'telehealth_url',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'confirmation_requested_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'rescheduled_at' => 'datetime',
            'check_in_completed_at' => 'datetime',
            'satisfaction_at' => 'datetime',
            'return_due_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment) {

            if (! $appointment->code) {
                $appointment->code = static::generateCode();
            }

            if (! $appointment->public_token) {
                $appointment->public_token = (string) Str::uuid();
            }
        });
    }

    public static function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $value = '';

            for ($i = 0; $i < 6; $i++) {
                $value .= $alphabet[
                    random_int(0, strlen($alphabet) - 1)
                ];
            }

            $code = 'AG-' . $value;

        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(AppointmentSeries::class, 'series_id');
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function clinicalRecord(): HasOne
    {
        return $this->hasOne(AppointmentClinicalRecord::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AppointmentEvent::class)
            ->orderByDesc('occurred_at');
    }

    public function clinicalScales(): HasMany
    {
        return $this->hasMany(AppointmentClinicalScale::class)
            ->orderByDesc('recorded_at');
    }

    public function carePlans(): HasMany
    {
        return $this->hasMany(ClinicalCarePlan::class)
            ->orderByDesc('id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'awaiting_confirmation' => 'Aguardando confirmação',
            'confirmed' => 'Confirmado',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
            'no_show' => 'Falta',
            default => 'Agendado',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'confirmed' => '#16a34a',
            'completed' => '#2563eb',
            'cancelled' => '#94a3b8',
            'no_show' => '#dc2626',
            'awaiting_confirmation' => '#f59e0b',
            default => '#2563eb',
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'confirmed' => 'success',
            'completed' => 'primary',
            'cancelled' => 'secondary',
            'no_show' => 'danger',
            'awaiting_confirmation' => 'warning',
            default => 'info',
        };
    }
}
