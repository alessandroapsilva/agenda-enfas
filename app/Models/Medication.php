<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    use HasUlids;

    protected $fillable = [
        'organization_id','code','name','generic_name','presentation',
        'pharmaceutical_form','concentration','unit','is_controlled',
        'is_active','minimum_stock','metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_controlled'=>'boolean','is_active'=>'boolean',
            'minimum_stock'=>'decimal:3','metadata'=>'array',
        ];
    }

    public function lots(): HasMany
    {
        return $this->hasMany(StockLot::class);
    }
}
