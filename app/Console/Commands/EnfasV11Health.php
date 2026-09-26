<?php

namespace App\Console\Commands;

use App\Models\MetaIntegration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EnfasV11Health extends Command
{
    protected $signature = 'enfas:v11-health';

    protected $description =
        'Diagnóstico operacional do ENFAS Agenda V11';

    public function handle(): int
    {
        $checks = [];

        /*
         * Banco
         */
        try {
            DB::select('SELECT 1');

            $checks['database'] = [
                'status' => 'OK',
                'detail' => 'MySQL conectado',
            ];
        } catch (Throwable $e) {
            $checks['database'] = [
                'status' => 'FAIL',
                'detail' => $e->getMessage(),
            ];
        }

        /*
         * Cache / Redis
         */
        try {
            $key = 'enfas.v11.health.'.uniqid();

            Cache::put(
                $key,
                'ok',
                now()->addMinute()
            );

            $ok = Cache::get($key) === 'ok';

            Cache::forget($key);

            $checks['cache'] = [
                'status' => $ok ? 'OK' : 'FAIL',
                'detail' => $ok
                    ? 'Cache operacional'
                    : 'Falha de leitura/escrita',
            ];
        } catch (Throwable $e) {
            $checks['cache'] = [
                'status' => 'FAIL',
                'detail' => $e->getMessage(),
            ];
        }

        /*
         * Storage
         */
        $checks['storage'] = [
            'status' =>
                is_link(public_path('storage'))
                    ? 'OK'
                    : 'FAIL',

            'detail' =>
                is_link(public_path('storage'))
                    ? 'public/storage linkado'
                    : 'public/storage sem link',
        ];

        /*
         * WhatsApp / Meta
         */
        $integration =
            MetaIntegration::query()->first();

        $checks['meta'] = [
            'status' =>
                $integration
                && filled($integration->waba_id)
                && filled($integration->phone_number_id)
                && filled($integration->access_token)
                    ? 'OK'
                    : 'FAIL',

            'detail' =>
                $integration
                    ? 'WABA '.$integration->waba_id
                      .' | Phone ID '
                      .$integration->phone_number_id
                    : 'Integração inexistente',
        ];

        /*
         * Webhook
         */
        if (
            Schema::hasTable(
                'wa_webhook_events'
            )
        ) {
            $lastWebhook =
                DB::table(
                    'wa_webhook_events'
                )
                ->orderByDesc('id')
                ->first();

            $checks['webhook'] = [
                'status' =>
                    $lastWebhook
                        ? 'OK'
                        : 'WARN',

                'detail' =>
                    $lastWebhook
                        ? 'Último evento #'
                          .$lastWebhook->id
                          .' em '
                          .($lastWebhook->received_at
                            ?? $lastWebhook->created_at)
                        : 'Nenhum webhook recebido',
            ];
        }

        /*
         * Automação
         */
        if (
            Schema::hasTable(
                'wa_automations'
            )
        ) {
            $checks['automations'] = [
                'status' => 'OK',
                'detail' =>
                    DB::table(
                        'wa_automations'
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->count()
                    .' ativa(s) de '
                    .DB::table(
                        'wa_automations'
                    )->count(),
            ];
        }

        /*
         * Mensagens
         */
        if (
            Schema::hasTable(
                'wa_messages'
            )
        ) {
            $checks['messages'] = [
                'status' => 'OK',
                'detail' =>
                    'sent='
                    .DB::table(
                        'wa_messages'
                    )
                    ->where(
                        'status',
                        'sent'
                    )->count()

                    .' delivered='
                    .DB::table(
                        'wa_messages'
                    )
                    ->where(
                        'status',
                        'delivered'
                    )->count()

                    .' read='
                    .DB::table(
                        'wa_messages'
                    )
                    ->where(
                        'status',
                        'read'
                    )->count()

                    .' failed='
                    .DB::table(
                        'wa_messages'
                    )
                    ->where(
                        'status',
                        'failed'
                    )->count(),
            ];
        }

        /*
         * Scheduler / Queue heartbeats
         */
        $checks['scheduler'] = [
            'status' =>
                Cache::get(
                    'enfas.scheduler.heartbeat'
                )
                    ? 'OK'
                    : 'WARN',

            'detail' =>
                Cache::get(
                    'enfas.scheduler.heartbeat'
                )
                    ?: 'Heartbeat ausente',
        ];

        $checks['queue'] = [
            'status' =>
                Cache::get(
                    'enfas.queue.heartbeat'
                )
                    ? 'OK'
                    : 'WARN',

            'detail' =>
                Cache::get(
                    'enfas.queue.heartbeat'
                )
                    ?: 'Heartbeat ausente',
        ];

        $rows = [];

        foreach (
            $checks as $name => $check
        ) {
            $rows[] = [
                strtoupper($name),
                $check['status'],
                $check['detail'],
            ];
        }

        $this->newLine();

        $this->info(
            'ENFAS Agenda V11 - Health Check'
        );

        $this->newLine();

        $this->table(
            [
                'Componente',
                'Status',
                'Detalhe',
            ],
            $rows
        );

        $fail = collect($checks)
            ->contains(
                fn ($check) =>
                    $check['status'] === 'FAIL'
            );

        return $fail
            ? self::FAILURE
            : self::SUCCESS;
    }
}
