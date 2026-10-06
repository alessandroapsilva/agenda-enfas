<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use HasUlids;

    protected $fillable = [
        'patient_id','encounter_id','prescriber_id','status','issued_at',
        'signed_at','signature_provider','signature_hash','notes',
    ];

    protected function casts(): array
    {
        return ['issued_at'=>'datetime','signed_at'=>'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }
}
