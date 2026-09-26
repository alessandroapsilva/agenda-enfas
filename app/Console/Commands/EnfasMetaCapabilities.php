<?php

namespace App\Console\Commands;

use App\Models\MetaIntegration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class EnfasMetaCapabilities extends Command
{
    protected $signature = 'enfas:meta-capabilities';
    protected $description = 'Valida capacidades Meta sem exibir segredos';

    public function handle(): int
    {
        $i = MetaIntegration::first();

        if (! $i) {
            $this->error('Integração Meta não encontrada.');
            return self::FAILURE;
        }

        $base = 'https://graph.facebook.com/' . ($i->graph_version ?: 'v25.0');
        $http = Http::withToken($i->access_token)->acceptJson()->timeout(20);

        $phone = $http->get($base . '/' . $i->phone_number_id, [
            'fields' => 'id,display_phone_number,verified_name,quality_rating,platform_type,is_on_biz_app',
        ]);

        $templates = $http->get($base . '/' . $i->waba_id . '/message_templates', ['limit' => 1]);
        $permissions = $http->get($base . '/me/permissions');
        $subscriptions = $http->get($base . '/' . $i->waba_id . '/subscribed_apps');

        $this->table(['Capacidade', 'Status'], [
            ['Phone Number', $phone->successful() ? 'OK - ' . ($phone->json('display_phone_number') ?: '-') : 'FALHA HTTP ' . $phone->status()],
            ['Plataforma', $phone->json('platform_type') ?: '-'],
            ['Qualidade', $phone->json('quality_rating') ?: '-'],
            ['Listar templates', $templates->successful() ? 'OK' : 'FALHA HTTP ' . $templates->status()],
            ['Criar templates por API', config('enfas_runtime.meta_template_create_api', false) ? 'HABILITADO' : 'BLOQUEADO PELA META / DESABILITADO NO ENFAS'],
            ['Permissões', $permissions->successful() ? collect($permissions->json('data', []))->where('status', 'granted')->pluck('permission')->implode(', ') : 'FALHA HTTP ' . $permissions->status()],
            ['Webhook / subscribed_apps', $subscriptions->successful() ? 'OK - ' . count($subscriptions->json('data', [])) . ' app(s)' : 'FALHA HTTP ' . $subscriptions->status()],
        ]);

        return $phone->successful() && $templates->successful()
            ? self::SUCCESS
            : self::FAILURE;
    }
}
