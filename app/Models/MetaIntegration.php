<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaIntegration extends Model
{
    protected $fillable = [
        'name','waba_id','phone_number_id','business_id','graph_version',
        'access_token','app_secret','verify_token','display_phone_number',
        'verified_name','quality_rating','is_active','last_tested_at'
    ];

    protected function casts(): array
    {
        return [
            'access_token'=>'encrypted',
            'app_secret'=>'encrypted',
            'verify_token'=>'encrypted',
            'is_active'=>'boolean',
            'last_tested_at'=>'datetime',
        ];
    }
}
