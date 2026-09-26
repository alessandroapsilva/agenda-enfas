<?php

namespace App\Http\Controllers\Enfas;

use App\Http\Controllers\Controller;
use App\Models\MetaIntegration;
use App\Models\WaConversation;
use App\Models\WaMessage;
use App\Services\Enfas\MetaWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class WhatsAppController extends Controller
{
    public function index(Request $request)
    {
        $integration = MetaIntegration::first();

        $conversations = WaConversation::query()
            ->with(['patient','appointment.service','appointment.professional','assignedUser'])
            ->whereIn('status', ['active','completed'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $search = trim($request->string('q')->toString());
                $q->where(function ($inner) use ($search) {
                    $inner->where('phone', 'like', '%'.$search.'%')
                        ->orWhereHas('patient', fn ($p) => $p->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($request->filled('mode'), fn ($q) => $q->where('mode', $request->string('mode')->toString()))
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $selected = null;
        $messages = collect();

        if ($request->filled('conversation')) {
            $selected = WaConversation::with([
                'patient',
                'appointment.service',
                'appointment.professional',
                'assignedUser',
            ])->find($request->integer('conversation'));

            if ($selected) {
                $messages = WaMessage::query()
                    ->where(function ($q) use ($selected) {
                        if ($selected->patient_id) {
                            $q->where('patient_id', $selected->patient_id);
                        } else {
                            $q->where('recipient', $selected->phone);
                        }
                    })
                    ->when($selected->appointment_id, fn ($q) =>
                        $q->where(function ($inner) use ($selected) {
                            $inner->where('appointment_id', $selected->appointment_id)
                                ->orWhereNull('appointment_id');
                        })
                    )
                    ->orderBy('id')
                    ->limit(250)
                    ->get();

                $selected->update(['unread_count' => 0]);
            }
        }

        $stats = [
            'active' => WaConversation::where('status','active')->count(),
            'human' => WaConversation::where('status','active')->where('mode','human')->count(),
            'bot' => WaConversation::where('status','active')->where('mode','bot')->count(),
            'unread' => WaConversation::where('status','active')->sum('unread_count'),
        ];

        return view('enfas.whatsapp.central', compact(
            'integration',
            'conversations',
            'selected',
            'messages',
            'stats'
        ));
    }

    public function thread(WaConversation $conversation)
    {
        return redirect()->route('enfas.whatsapp', [
            'conversation' => $conversation->id,
        ]);
    }

    public function takeover(WaConversation $conversation)
    {
        $conversation->update([
            'mode' => 'human',
            'assigned_user_id' => auth()->id(),
            'human_taken_at' => now(),
            'status' => 'active',
        ]);

        return back()->with('success', 'Atendimento assumido pela equipe.');
    }

    public function release(WaConversation $conversation)
    {
        $conversation->update([
            'mode' => 'bot',
            'assigned_user_id' => null,
            'human_taken_at' => null,
            'status' => 'active',
        ]);

        return back()->with('success', 'Conversa devolvida ao robô.');
    }

    public function close(WaConversation $conversation)
    {
        $conversation->update([
            'status' => 'completed',
            'closed_at' => now(),
            'unread_count' => 0,
        ]);

        return back()->with('success', 'Conversa encerrada.');
    }

    public function sendConversationMessage(
        Request $request,
        WaConversation $conversation,
        MetaWhatsAppService $meta
    ) {
        $data = $request->validate([
            'message' => ['required','string','min:1','max:4000'],
        ]);

        try {
            $message = $meta->sendTextMessage(
                $conversation->phone,
                trim($data['message']),
                $conversation->appointment_id,
                $conversation->patient_id
            );
        } catch (\Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'message' => $e->getMessage(),
            ]);
        }

        $conversation->update([
            'mode' => 'human',
            'assigned_user_id' => auth()->id(),
            'human_taken_at' => $conversation->human_taken_at ?: now(),
            'last_message_at' => now(),
            'last_outbound_at' => now(),
            'status' => 'active',
        ]);

        return back()->with('success', 'Mensagem enviada. Status: '.$message->status.'.');
    }

    public function save(Request $request)
    {
        $data=$request->validate([
            'name'=>'required|string|max:120',
            'waba_id'=>'nullable|string|max:120',
            'phone_number_id'=>'nullable|string|max:120',
            'business_id'=>'nullable|string|max:120',
            'graph_version'=>['required','regex:/^v[0-9]+\.[0-9]+$/'],
            'access_token'=>'nullable|string',
            'app_secret'=>'nullable|string',
            'verify_token'=>'nullable|string',
        ]);

        $integration=MetaIntegration::first();

        if($integration){
            foreach(['access_token','app_secret','verify_token'] as $secret){
                if(blank($data[$secret] ?? null)) unset($data[$secret]);
            }
            $integration->update($data);
        } else {
            $integration=MetaIntegration::create($data);
        }

        return back()->with('success','Configuração da Meta salva.');
    }

    public function test()
    {
        $integration=MetaIntegration::firstOrFail();

        if(!$integration->phone_number_id || !$integration->access_token){
            return back()->withErrors(['meta'=>'Informe Phone Number ID e Access Token antes de testar.']);
        }

        try{
            $response=Http::withToken($integration->access_token)
                ->acceptJson()->timeout(20)
                ->get('https://graph.facebook.com/'.$integration->graph_version.'/'.$integration->phone_number_id,[
                    'fields'=>'display_phone_number,verified_name,quality_rating'
                ]);

            if(!$response->successful()){
                return back()->withErrors(['meta'=>$response->json('error.message') ?: 'A Meta recusou a conexão.']);
            }

            $integration->update([
                'display_phone_number'=>$response->json('display_phone_number'),
                'verified_name'=>$response->json('verified_name'),
                'quality_rating'=>$response->json('quality_rating'),
                'last_tested_at'=>now(),
            ]);

            return back()->with('success','Conexão oficial com a Meta validada.');
        } catch(\Throwable $e){
            report($e);
            return back()->withErrors(['meta'=>'Não foi possível conectar à Meta. Consulte o log para detalhes.']);
        }
    }

    private function tableView(string $view,string $table)
    {
        return view($view,[
            'ready'=>Schema::hasTable($table),
            'rows'=>Schema::hasTable($table) ? DB::table($table)->orderByDesc('id')->limit(100)->get() : collect()
        ]);
    }

    public function templates(){ return $this->tableView('enfas.whatsapp.templates','whatsapp_templates'); }
    public function automations(){ return $this->tableView('enfas.whatsapp.automations','automation_rules'); }
    public function messages(){ return $this->tableView('enfas.whatsapp.messages','whatsapp_messages'); }
}
