<?php
namespace App\Http\Controllers\Enfas\V6\Meta;

use App\Http\Controllers\Controller;
use App\Models\MetaIntegration;
use App\Models\WaMessage;
use App\Models\WaTemplate;
use App\Models\WaWebhookEvent;
use App\Services\Enfas\WhatsAppAutomationEngine;
use App\Services\Enfas\WhatsAppConversationEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $i = MetaIntegration::first();
        if (! $i || ! $i->verify_token) return response('Not configured',403);

        if ($request->query('hub_mode') === 'subscribe'
            && hash_equals((string)$i->verify_token,(string)$request->query('hub_verify_token'))) {
            return response($request->query('hub_challenge'),200)->header('Content-Type','text/plain');
        }

        return response('Forbidden',403);
    }

    public function receive(Request $request, WhatsAppAutomationEngine $automation, WhatsAppConversationEngine $conversation)
    {
        $i = MetaIntegration::first();

        if ($i?->app_secret) {
            $sig = (string)$request->header('X-Hub-Signature-256');
            $expected = 'sha256='.hash_hmac('sha256',$request->getContent(),$i->app_secret);
            if (! $sig || ! hash_equals($expected,$sig)) return response('Invalid signature',401);
        }

        $event = WaWebhookEvent::create([
            'event_type'=>'whatsapp','payload'=>$request->all(),'received_at'=>now()
        ]);

        try {
            foreach ($request->input('entry',[]) as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];

                    if (($change['field'] ?? '') === 'message_template_status_update') {
                        $language = str_replace(
                            '-',
                            '_',
                            (string) ($value['message_template_language'] ?? 'pt_BR')
                        );

                        $template = WaTemplate::query()
                            ->when(
                                ! empty($value['message_template_id']),
                                fn ($q) => $q->where(
                                    'meta_template_id',
                                    $value['message_template_id']
                                )
                            )
                            ->when(
                                empty($value['message_template_id']),
                                fn ($q) => $q
                                    ->where(
                                        'name',
                                        $value['message_template_name'] ?? ''
                                    )
                                    ->where('language', $language)
                            )
                            ->first();

                        if ($template) {
                            $template->update([
                                'status' => $value['event'] ?? $template->status,
                                'rejection_reason' => $value['reason'] ?? null,
                                'last_synced_at' => now(),
                            ]);
                        }

                        continue;
                    }

                    foreach ($value['statuses'] ?? [] as $status) {
                        $m = WaMessage::where('meta_message_id',$status['id'] ?? '')->first();
                        if (! $m) continue;

                        $state = $status['status'] ?? 'unknown';
                        $u = ['status'=>$state];
                        if ($state === 'delivered') $u['delivered_at'] = now();
                        if ($state === 'read') $u['read_at'] = now();
                        if ($state === 'failed') {
                            $u['failed_at'] = now();
                            $u['error_message'] = data_get($status,'errors.0.title') ?: data_get($status,'errors.0.message');
                        }
                        $m->update($u);
                        if ($m->appointment_id) $this->event(
                            $m->appointment_id,'whatsapp_'.$state,'WhatsApp '.$state,$u['error_message'] ?? null
                        );
                    }

                    foreach ($value['messages'] ?? [] as $incoming) {
                        $conversation->handle($incoming);
                    }}
                }
            }
            $event->update(['processed'=>true]);
        } catch(\Throwable $e) {
            report($e);
            $event->update(['processing_error'=>$e->getMessage()]);
        }

        return response('EVENT_RECEIVED',200);
    }

    private function event(int $appointmentId,string $type,string $title,?string $description=null): void
    {
        if (! Schema::hasTable('appointment_events')) return;
        DB::table('appointment_events')->insert([
            'appointment_id'=>$appointmentId,
            'user_id'=>null,
            'event_type'=>$type,
            'title'=>$title,
            'description'=>$description,
            'metadata'=>null,
            'occurred_at'=>now(),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
    }
}
