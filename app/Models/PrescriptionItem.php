<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'prescription_id','medication_id','medication_name','dose','route',
        'frequency','duration_days','quantity','instructions','status',
    ];

    protected function casts(): array
    {
        return ['quantity'=>'decimal:3'];
    }
}
