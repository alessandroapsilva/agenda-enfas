<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentProtocolResponse extends Model
{
    protected $fillable = [
        'protocol_run_id',
        'template_item_id',
        'value',
        'notes',
        'recorded_by',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AppointmentProtocolRun::class, 'protocol_run_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ClinicalProtocolTemplateItem::class, 'template_item_id');
    }
}
