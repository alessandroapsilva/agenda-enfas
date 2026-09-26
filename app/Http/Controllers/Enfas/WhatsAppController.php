<?php

namespace App\Http\Controllers\Enfas;

use App\Http\Controllers\Controller;
use App\Models\MetaIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class WhatsAppController extends Controller
{
    public function index()
    {
        return view('enfas.whatsapp.central',['integration'=>MetaIntegration::first()]);
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
