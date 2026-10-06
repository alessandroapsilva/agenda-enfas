<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLot extends Model
{
    use HasUlids;

    protected $fillable = [
        'pharmacy_id','medication_id','batch','expires_at','quantity',
        'reserved_quantity','unit_cost','status',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'=>'date','quantity'=>'decimal:3',
            'reserved_quantity'=>'decimal:3','unit_cost'=>'decimal:4',
        ];
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }
}
