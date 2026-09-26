<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaWebhookEvent extends Model
{
    protected $table = 'wa_webhook_events';
    protected $fillable = ['event_type','payload','processed','processing_error','received_at'];
    protected function casts(): array
    {
        return ['payload'=>'array','processed'=>'boolean','received_at'=>'datetime'];
    }
}
