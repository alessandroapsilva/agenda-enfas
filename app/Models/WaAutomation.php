<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaAutomation extends Model
{
    protected $table = 'wa_automations';
    protected $fillable = [
        'name','trigger_event','offset_minutes','template_id','service_id',
        'send_once','retry_count','is_active'
    ];
    protected function casts(): array
    {
        return ['send_once'=>'boolean','is_active'=>'boolean'];
    }
    public function template()
    {
        return $this->belongsTo(WaTemplate::class, 'template_id');
    }
}
