<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalProtocolTemplateItem extends Model
{
    protected $fillable = [
        'template_id',
        'label',
        'description',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ClinicalProtocolTemplate::class, 'template_id');
    }
}
