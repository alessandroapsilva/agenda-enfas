<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Encounter extends Model
{
    use HasUlids;

    protected $fillable = [
        'patient_id','facility_id','professional_id','type','status',
        'checkin_at','started_at','ended_at','metadata',
    ];

    protected function casts(): array
    {
        return [
            'checkin_at'=>'datetime','started_at'=>'datetime',
            'ended_at'=>'datetime','metadata'=>'array',
        ];
    }
}
