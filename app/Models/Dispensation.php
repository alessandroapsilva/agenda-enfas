<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispensation extends Model
{
    use HasUlids;

    protected $fillable = [
        'pharmacy_id','patient_id','prescription_id','status',
        'dispensed_by','dispensed_at','notes',
    ];

    protected function casts(): array
    {
        return ['dispensed_at'=>'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(DispensationItem::class);
    }
}
