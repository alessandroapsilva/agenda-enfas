<?php

namespace App\Console\Commands;

use App\Models\MetaIntegration;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnfasProductionHealth extends Command
{
    protected $signature = 'enfas:health {--render : Executa as rotas GET criticas por dentro do Laravel}';

    protected $description = 'Valida a prontidao real de producao do ENFAS Agenda';

    private array $failures = [];

    public function handle(): int
    {
        $this->newLine();
        $this->info('ENFAS Agenda - Health Check Real');
        $this->line(str_repeat('=', 58));

        $this->check(
            'Ambiente production',
            app()->environment('production')
        );

        $this->check(
            'APP_DEBUG desativado',
            config('app.debug') === false
        );

        $this->check(
            'Timezone America/Sao_Paulo',
            config('app.timezone') === 'America/Sao_Paulo'
        );

        foreach ([
            'users',
            'patients',
            'professionals',
            'services',
            'appointments',
            'app_settings',
            'meta_integrations',
            'wa_templates',
            'wa_automations',
            'wa_messages',
            'wa_webhook_events',
        ] as $table) {
            $this->check(
                'Tabela ' . $table,
                Schema::hasTable($table)
            );
        }

        $integration = MetaIntegration::first();

        $this->check(
            'Meta configurada',
            (bool) $integration
        );

        $this->check(
            'Access Token salvo',
            filled($integration?->access_token)
        );

        $this->check(
            'WABA ID salvo',
            filled($integration?->waba_id)
        );

        $this->check(
            'Phone Number ID salvo',
            filled($integration?->phone_number_id)
        );

        $this->check(
            'Storage link',
            is_link(public_path('storage'))
        );

        $this->check(
            'Storage gravavel',
            is_writable(storage_path())
        );

        $this->check(
            'Bootstrap cache gravavel',
            is_writable(base_path('bootstrap/cache'))
        );

        $uris = [
            '/dashboard' => 'Dashboard',
            '/agenda' => 'Agenda',
            '/agendamentos' => 'Agendamentos',
            '/pacientes' => 'Pacientes',
            '/profissionais' => 'Profissionais',
            '/servicos' => 'Servicos',
            '/locais' => 'Locais',
            '/disponibilidade' => 'Disponibilidade',
            '/campos-personalizados' => 'Campos personalizados',
            '/whatsapp' => 'WhatsApp central',
            '/whatsapp/templates' => 'WhatsApp modelos',
            '/whatsapp/automacoes' => 'WhatsApp automacoes',
            '/whatsapp/mensagens' => 'WhatsApp historico',
            '/relatorios' => 'Relatorios',
            '/alertas' => 'Alertas',
            '/usuarios' => 'Usuarios',
            '/auditoria' => 'Auditoria',
            '/configuracoes/aparencia' => 'Aparencia',
            '/configuracoes/agenda' => 'Preferencias agenda',
        ];

        foreach ($uris as $uri => $label) {
            $this->checkRouteExists(
                $uri,
                $label
            );
        }

        if ($this->option('render')) {
            $this->renderCriticalRoutes($uris);
        }

        $this->newLine();

        if ($this->failures === []) {
            $this->info('PRODUCAO: APROVADA');
            return self::SUCCESS;
        }

        $this->error(
            'PRODUCAO: BLOQUEADA - '
            . count($this->failures)
            . ' verificacao(oes) falharam.'
        );

        foreach ($this->failures as $failure) {
            $this->line(' - ' . $failure);
        }

        return self::FAILURE;
    }

    private function checkRouteExists(
        string $uri,
        string $label
    ): void {
        try {
            $request = Request::create(
                $uri,
                'GET'
            );

            app('router')
                ->getRoutes()
                ->match($request);

            $this->pass(
                'Rota ' . $label . ' [' . $uri . ']'
            );
        } catch (\Throwable $e) {
            $this->recordFailure(
                'Rota ' . $label . ' [' . $uri . ']',
                $e->getMessage()
            );
        }
    }

    private function renderCriticalRoutes(
        array $uris
    ): void {
        $this->newLine();
        $this->info('Renderizacao das rotas criticas');

        ViewFacade::share(
            'errors',
            new ViewErrorBag()
        );

        $user = User::query()->first();

        if ($user) {
            Auth::login($user);
        }

        $originalRequest = app('request');

        foreach ($uris as $uri => $label) {
            try {
                $request = Request::create(
                    $uri,
                    'GET'
                );

                $request->setUserResolver(
                    fn () => $user
                );

                app()->instance(
                    'request',
                    $request
                );

                $route = app('router')
                    ->getRoutes()
                    ->match($request);

                $route->setContainer(app());
                $route->bind($request);

                $result = $route->run();

                if ($result instanceof View) {
                    $html = $result->render();

                    if (strlen($html) < 100) {
                        throw new \RuntimeException(
                            'A view retornou HTML vazio ou incompleto.'
                        );
                    }
                }

                $this->pass(
                    'Render ' . $label
                );
            } catch (\Throwable $e) {
                $this->recordFailure(
                    'Render ' . $label,
                    $e->getMessage()
                );
            } finally {
                app()->instance(
                    'request',
                    $originalRequest
                );
            }
        }
    }

    private function check(
        string $label,
        bool $condition
    ): void {
        if ($condition) {
            $this->pass($label);
            return;
        }

        $this->recordFailure($label);
    }

    private function pass(
        string $label
    ): void {
        $this->line(
            '<fg=green>OK</>   ' . $label
        );
    }

    private function recordFailure(
        string $label,
        ?string $detail = null
    ): void {
        $message = $label;

        if ($detail) {
            $message .= ' :: ' . $detail;
        }

        $this->failures[] = $message;

        $this->line(
            '<fg=red>FAIL</> ' . $message
        );
    }
}
