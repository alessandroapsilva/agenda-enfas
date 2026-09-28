<?php

namespace App\Services\Enfas;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AccessScopeService
{
    public function appointments(Builder $query, ?User $user): Builder
    {
        if (! $this->isProfessional($user)) {
            return $query;
        }

        if (! $user->professional_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('professional_id', $user->professional_id);
    }

    public function patients(Builder $query, ?User $user): Builder
    {
        if (! $this->isProfessional($user)) {
            return $query;
        }

        if (! $user->professional_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('appointments', function (Builder $appointments) use ($user) {
            $appointments->where('professional_id', $user->professional_id);
        });
    }

    public function professionals(Builder $query, ?User $user): Builder
    {
        if (! $this->isProfessional($user)) {
            return $query;
        }

        if (! $user->professional_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereKey($user->professional_id);
    }

    public function canViewAppointment(?User $user, Appointment $appointment): bool
    {
        if (! $this->isProfessional($user)) {
            return true;
        }

        return (int) $appointment->professional_id === (int) $user->professional_id;
    }

    public function canViewPatient(?User $user, Patient $patient): bool
    {
        if (! $this->isProfessional($user)) {
            return true;
        }

        if (! $user->professional_id) {
            return false;
        }

        return $patient->appointments()
            ->where('professional_id', $user->professional_id)
            ->exists();
    }

    public function canUseProfessional(?User $user, int $professionalId): bool
    {
        if (! $this->isProfessional($user)) {
            return true;
        }

        return (int) $user->professional_id === $professionalId;
    }

    private function isProfessional(?User $user): bool
    {
        return $user?->role === 'professional';
    }
}
