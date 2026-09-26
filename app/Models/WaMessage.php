<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaMessage extends Model
{
    protected $table = 'wa_messages';
    protected $fillable = [
        'appointment_id','patient_id','template_id','automation_id','direction',
        'message_type','meta_message_id','status','recipient','body','payload',
        'dedupe_key','sent_at','delivered_at','read_at','failed_at','error_message'
    ];
    protected function casts(): array
    {
        return [
            'payload'=>'array','sent_at'=>'datetime','delivered_at'=>'datetime',
            'read_at'=>'datetime','failed_at'=>'datetime',
        ];
    }
    public function template()
    {
        return $this->belongsTo(WaTemplate::class, 'template_id');
    }
}
