<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalDocumentTemplate extends Model
{
    protected $fillable = [
        'name','document_type','title','body_template',
        'requires_signature','is_active','sort_order',
        'created_by','updated_by',
    ];

    protected function casts(): array
    {
        return [
            'requires_signature' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
