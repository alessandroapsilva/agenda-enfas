<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class EnfasProductionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $systemName = 'ENFAS Agenda';
        $primary = '#2563eb';
        $logo = null;
        $favicon = null;

        try {
            if (Schema::hasTable('app_settings')) {
                $systemName = AppSetting::getValue(
                    'branding',
                    'system_name',
                    $systemName
                );

                $primary = AppSetting::getValue(
                    'branding',
                    'primary_color',
                    $primary
                );

                $logo = AppSetting::getValue(
                    'branding',
                    'logo'
                );

                $favicon = AppSetting::getValue(
                    'branding',
                    'favicon'
                );
            }
        } catch (\Throwable) {
            // Mantém defaults durante comandos de manutenção.
        }

        $logoPath = $logo
            ? 'storage/' . ltrim($logo, '/')
            : 'assets/brand/enfas-agenda.svg';

        $faviconPath = $favicon
            ? 'storage/' . ltrim($favicon, '/')
            : 'assets/brand/enfas-agenda.svg';

        config([
            'enfas.logo_path' => $logoPath,
            'enfas.favicon_path' => $faviconPath,

            'adminlte.title' => $systemName,
            'adminlte.logo' =>
                '<strong>ENFAS</strong> '
                . '<span class="fw-light">Agenda</span>',
            'adminlte.logo_img' => $logoPath,
            'adminlte.logo_img_alt' => $systemName,
            'adminlte.logo_img_class' =>
                'brand-image rounded-3 shadow-sm',

            'adminlte.auth_logo' => [
                'enabled' => true,
                'img' => [
                    'path' => $logoPath,
                    'alt' => $systemName,
                    'class' => 'rounded-3',
                    'width' => 64,
                    'height' => 64,
                ],
            ],

            'adminlte.layout_fixed_sidebar' => true,
            'adminlte.layout_fixed_navbar' => true,
            'adminlte.layout_dark_mode' => null,

            'adminlte.sidebar_mini' => true,
            'adminlte.sidebar_collapse' => true,
            'adminlte.sidebar_collapse_auto_size' => true,
            'adminlte.sidebar_theme' => 'dark',
            'adminlte.sidebar_docs_url' => false,

            'adminlte.color_mode_toggle' => true,

            'adminlte.usermenu_enabled' => true,
            'adminlte.usermenu_header' => false,
            'adminlte.usermenu_image' => false,
            'adminlte.usermenu_desc' => false,
            'adminlte.usermenu_profile_url' => false,

            'adminlte.demo' => false,
            'adminlte.docs' => false,
            'adminlte.preloader' => false,

            'adminlte.primary_color' => $primary,
            'adminlte.sidebar_color' => '#0f172a',
            'adminlte.navbar_color' => '#ffffff',
            'adminlte.footer_color' => '#ffffff',

            'adminlte.classes_body' =>
                'enfas-shell sidebar-without-hover',

            'adminlte.footer_left' =>
                '<strong>ENFAS Agenda</strong>',
            'adminlte.footer_right' => '',

            'adminlte.menu' => [
                ['header' => 'OPERAÇÃO'],
                [
                    'text' => 'Visão geral',
                    'url' => 'dashboard',
                    'icon' => 'bi bi-grid-1x2',
                ],
                [
                    'text' => 'Agenda',
                    'url' => 'agenda',
                    'icon' => 'bi bi-calendar3',
                ],
                [
                    'text' => 'Agendamentos',
                    'url' => 'agendamentos',
                    'icon' => 'bi bi-calendar2-check',
                ],
                [
                    'text' => 'Pacientes',
                    'url' => 'pacientes',
                    'icon' => 'bi bi-people',
                ],

                ['header' => 'ESTRUTURA'],
                [
                    'text' => 'Cadastros',
                    'icon' => 'bi bi-layers',
                    'submenu' => [
                        [
                            'text' => 'Profissionais',
                            'url' => 'profissionais',
                            'icon' => 'bi bi-person-badge',
                        ],
                        [
                            'text' => 'Serviços',
                            'url' => 'servicos',
                            'icon' => 'bi bi-grid',
                        ],
                        [
                            'text' => 'Locais',
                            'url' => 'locais',
                            'icon' => 'bi bi-geo-alt',
                        ],
                        [
                            'text' => 'Disponibilidade',
                            'url' => 'disponibilidade',
                            'icon' => 'bi bi-clock-history',
                        ],
                        [
                            'text' => 'Campos personalizados',
                            'url' => 'campos-personalizados',
                            'icon' => 'bi bi-ui-checks-grid',
                        ],
                    ],
                ],

                ['header' => 'COMUNICAÇÃO'],
                [
                    'text' => 'WhatsApp',
                    'icon' => 'bi bi-whatsapp',
                    'submenu' => [
                        [
                            'text' => 'Central Meta',
                            'url' => 'whatsapp',
                            'icon' => 'bi bi-cloud-check',
                        ],
                        [
                            'text' => 'Templates',
                            'url' => 'whatsapp/templates',
                            'icon' => 'bi bi-chat-square-text',
                        ],
                        [
                            'text' => 'Automações',
                            'url' => 'whatsapp/automacoes',
                            'icon' => 'bi bi-lightning-charge',
                        ],
                        [
                            'text' => 'Histórico',
                            'url' => 'whatsapp/mensagens',
                            'icon' => 'bi bi-clock-history',
                        ],
                    ],
                ],

                ['header' => 'GESTÃO'],
                [
                    'text' => 'Relatórios',
                    'url' => 'relatorios',
                    'icon' => 'bi bi-bar-chart',
                ],
                [
                    'text' => 'Alertas',
                    'url' => 'alertas',
                    'icon' => 'bi bi-bell',
                ],
                [
                    'text' => 'Usuários',
                    'url' => 'usuarios',
                    'icon' => 'bi bi-person-gear',
                ],
                [
                    'text' => 'Auditoria',
                    'url' => 'auditoria',
                    'icon' => 'bi bi-shield-check',
                ],

                ['header' => 'CONFIGURAÇÕES'],
                [
                    'text' => 'Sistema',
                    'icon' => 'bi bi-gear',
                    'submenu' => [
                        [
                            'text' => 'Aparência',
                            'url' => 'configuracoes/aparencia',
                            'icon' => 'bi bi-palette',
                        ],
                        [
                            'text' => 'Agenda',
                            'url' => 'configuracoes/agenda',
                            'icon' => 'bi bi-sliders',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
