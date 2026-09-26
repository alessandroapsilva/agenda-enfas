<?php

namespace App\Providers;

use App\Http\Middleware\Enfas\SecurityHeaders;
use App\Jobs\Enfas\QueueHeartbeatJob;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class EnfasV92ProductionServiceProvider extends ServiceProvider
{
    public function boot(Schedule $schedule): void
    {
        app('router')->pushMiddlewareToGroup(
            'web',
            SecurityHeaders::class
        );

        $schedule->call(function(){
            Cache::put(
                'enfas.scheduler.heartbeat',
                now()->toIso8601String(),
                now()->addMinutes(15)
            );
        })->everyMinute()->name('enfas-scheduler-heartbeat');

        $schedule->job(
            new QueueHeartbeatJob()
        )->everyMinute()->name('enfas-queue-heartbeat');

        $schedule->command(
            'enfas:backup --retention=14'
        )->dailyAt('03:17')->withoutOverlapping();

        Event::listen(Login::class,function(Login $event){
            $this->audit(
                $event->user->id??null,
                'auth',
                'login',
                'Login realizado'
            );
        });

        Event::listen(Logout::class,function(Logout $event){
            $this->audit(
                $event->user->id??null,
                'auth',
                'logout',
                'Logout realizado'
            );
        });

        config([
            'adminlte.title'=>'ENFAS Agenda',
            'adminlte.logo'=>'<b>ENFAS</b> <span style="font-weight:400">Agenda</span>',
            'adminlte.sidebar_mini'=>'lg',
            'adminlte.sidebar_nav_accordion'=>true,
            'adminlte.layout_topnav'=>false,
            'adminlte.layout_boxed'=>false,
            'adminlte.menu'=>[
                [
                    'text'=>'Hoje',
                    'url'=>'hoje',
                    'icon'=>'bi bi-grid-1x2',
                ],
                [
                    'text'=>'Confirmações',
                    'url'=>'confirmacoes/central',
                    'icon'=>'bi bi-check2-circle',
                ],
                [
                    'text'=>'Agenda',
                    'url'=>'agenda',
                    'icon'=>'bi bi-calendar3',
                ],
                [
                    'text'=>'Pacientes',
                    'url'=>'pacientes',
                    'icon'=>'bi bi-people',
                ],
                [
                    'text'=>'WhatsApp',
                    'icon'=>'bi bi-whatsapp',
                    'submenu'=>[
                        [
                            'text'=>'Mensagens',
                            'url'=>'whatsapp/mensagens',
                            'icon'=>'bi bi-chat-left-text',
                        ],
                        [
                            'text'=>'Mensagens automáticas',
                            'url'=>'whatsapp/templates',
                            'icon'=>'bi bi-file-earmark-text',
                        ],
                        [
                            'text'=>'Imagens das mensagens',
                            'url'=>'whatsapp/midia',
                            'icon'=>'bi bi-image',
                        ],
                        [
                            'text'=>'Conexão WhatsApp',
                            'url'=>'whatsapp',
                            'icon'=>'bi bi-link-45deg',
                        ],
                    ],
                ],
                [
                    'text'=>'Confirmações automáticas',
                    'url'=>'whatsapp/automacoes',
                    'icon'=>'bi bi-lightning-charge',
                ],
                [
                    'text'=>'Histórico de contatos',
                    'url'=>'comunicacoes',
                    'icon'=>'bi bi-clock-history',
                ],
                [
                    'text'=>'Resultados',
                    'url'=>'relatorios',
                    'icon'=>'bi bi-graph-up-arrow',
                ],
                [
                    'text'=>'Atendimento',
                    'icon'=>'bi bi-building',
                    'submenu'=>[
                        [
                            'text'=>'Profissionais',
                            'url'=>'profissionais',
                            'icon'=>'bi bi-person-badge',
                        ],
                        [
                            'text'=>'Serviços',
                            'url'=>'servicos',
                            'icon'=>'bi bi-list-check',
                        ],
                        [
                            'text'=>'Unidades',
                            'url'=>'locais',
                            'icon'=>'bi bi-geo-alt',
                        ],
                        [
                            'text'=>'Horários',
                            'url'=>'disponibilidade',
                            'icon'=>'bi bi-calendar-week',
                        ],
                    ],
                ],
                [
                    'text'=>'Ajustes',
                    'icon'=>'bi bi-sliders',
                    'submenu'=>[
                        [
                            'text'=>'Equipe',
                            'url'=>'usuarios',
                            'icon'=>'bi bi-people-fill',
                        ],
                        [
                            'text'=>'Alertas',
                            'url'=>'alertas',
                            'icon'=>'bi bi-bell',
                        ],
                        [
                            'text'=>'Auditoria',
                            'url'=>'auditoria',
                            'icon'=>'bi bi-shield-check',
                        ],
                        [
                            'text'=>'Funcionamento',
                            'url'=>'sistema/saude',
                            'icon'=>'bi bi-heart-pulse',
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function audit(
        ?int $userId,
        string $module,
        string $action,
        string $description
    ): void {
        try {
            if (! Schema::hasTable('audit_logs')) {
                return;
            }

            DB::table('audit_logs')->insert([
                'user_id'=>$userId,
                'module'=>$module,
                'action'=>$action,
                'description'=>$description,
                'ip'=>request()?->ip(),
                'user_agent'=>request()?->userAgent(),
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
