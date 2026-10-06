<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class DispensationItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'dispensation_id','prescription_item_id','medication_id','stock_lot_id','quantity',
    ];

    protected function casts(): array
    {
        return ['quantity'=>'decimal:3'];
    }
}
