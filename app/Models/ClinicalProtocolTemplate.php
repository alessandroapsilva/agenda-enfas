<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalProtocolTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClinicalProtocolTemplateItem::class, 'template_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AppointmentProtocolRun::class, 'template_id');
    }
}
