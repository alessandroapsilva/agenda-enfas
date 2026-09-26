<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name','username','email','phone','job_title','professional_id',
        'password','role','permissions','is_active','force_password_change',
        'last_login_at','last_seen_at',
    ];

    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
            'force_password_change' => 'boolean',
            'permissions' => 'array',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'admin' => 'Administrador',
            'supervisor' => 'Supervisor',
            'professional' => 'Profissional',
            default => 'Atendente',
        };
    }

    public function effectivePermissions(): array
    {
        $custom = $this->permissions ?? [];

        if ($custom !== []) {
            return $custom;
        }

        return config('enfas_permissions.roles.'.$this->role, []);
    }

    public function canAccess(string $permission): bool
    {
        $permissions = $this->effectivePermissions();

        return in_array('*', $permissions, true)
            || in_array($permission, $permissions, true);
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
