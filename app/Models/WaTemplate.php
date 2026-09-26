<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaTemplate extends Model
{
    protected $table = 'wa_templates';

    protected $fillable = [
        'meta_template_id',
        'name',
        'purpose',
        'category',
        'language',
        'status',
        'header_text',
        'body',
        'footer',
        'buttons',
        'variable_keys',
        'sample_values',
        'rejection_reason',
        'created_by',
        'last_synced_at',
        'is_active',
        'header_type',
        'header_media_id',
        'archived_at',
        'updated_by',
        'version',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'buttons' => 'array',
            'variable_keys' => 'array',
            'sample_values' => 'array',
            'last_synced_at' => 'datetime',
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
