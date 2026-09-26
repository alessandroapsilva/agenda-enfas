<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EnfasModernUiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        config([
            'adminlte.title'=>'ENFAS Agenda',
            'adminlte.title_prefix'=>'',
            'adminlte.title_postfix'=>'',
            'adminlte.logo'=>'<b>ENFAS</b> Agenda',
            'adminlte.logo_img_alt'=>'ENFAS Agenda',
            'adminlte.sidebar_mini'=>'lg',
            'adminlte.sidebar_collapse_remember'=>false,
            'adminlte.sidebar_nav_accordion'=>true,
            'adminlte.layout_topnav'=>false,
            'adminlte.layout_boxed'=>false,
        ]);

        config([
            'adminlte.menu'=>[
                [
                    'text'=>'Central',
                    'url'=>'inicio',
                    'icon'=>'bi bi-grid-1x2',
                ],
                [
                    'text'=>'Atendimento',
                    'icon'=>'bi bi-calendar3',
                    'submenu'=>[
                        [
                            'text'=>'Agenda',
                            'url'=>'agenda',
                            'icon'=>'bi bi-calendar3',
                        ],
                        [
                            'text'=>'Agendamentos',
                            'url'=>'agendamentos',
                            'icon'=>'bi bi-calendar2-check',
                        ],
                        [
                            'text'=>'Disponibilidade',
                            'url'=>'disponibilidade',
                            'icon'=>'bi bi-clock-history',
                        ],
                    ],
                ],
                [
                    'text'=>'Cadastros',
                    'icon'=>'bi bi-database',
                    'submenu'=>[
                        [
                            'text'=>'Profissionais',
                            'url'=>'profissionais',
                            'icon'=>'bi bi-person-badge',
                        ],
                        [
                            'text'=>'Pacientes',
                            'url'=>'pacientes',
                            'icon'=>'bi bi-people',
                        ],
                        [
                            'text'=>'Serviços',
                            'url'=>'servicos',
                            'icon'=>'bi bi-grid',
                        ],
                        [
                            'text'=>'Unidades e locais',
                            'url'=>'locais',
                            'icon'=>'bi bi-geo-alt',
                        ],
                        [
                            'text'=>'Campos personalizados',
                            'url'=>'campos-personalizados',
                            'icon'=>'bi bi-ui-checks-grid',
                        ],
                    ],
                ],
                [
                    'text'=>'WhatsApp',
                    'icon'=>'bi bi-whatsapp',
                    'submenu'=>[
                        [
                            'text'=>'Central',
                            'url'=>'whatsapp',
                            'icon'=>'bi bi-whatsapp',
                        ],
                        [
                            'text'=>'Modelos',
                            'url'=>'whatsapp/templates',
                            'icon'=>'bi bi-chat-square-text',
                        ],
                        [
                            'text'=>'Automações',
                            'url'=>'whatsapp/automacoes',
                            'icon'=>'bi bi-lightning-charge',
                        ],
                        [
                            'text'=>'Mensagens',
                            'url'=>'whatsapp/mensagens',
                            'icon'=>'bi bi-send',
                        ],
                        [
                            'text'=>'Biblioteca de mídia',
                            'url'=>'whatsapp/midia',
                            'icon'=>'bi bi-images',
                        ],
                    ],
                ],
                [
                    'text'=>'Gestão',
                    'icon'=>'bi bi-briefcase',
                    'submenu'=>[
                        [
                            'text'=>'Relatórios',
                            'url'=>'relatorios',
                            'icon'=>'bi bi-bar-chart',
                        ],
                        [
                            'text'=>'Auditoria',
                            'url'=>'auditoria',
                            'icon'=>'bi bi-shield-check',
                        ],
                        [
                            'text'=>'Alertas',
                            'url'=>'alertas',
                            'icon'=>'bi bi-bell',
                        ],
                        [
                            'text'=>'Usuários',
                            'url'=>'usuarios',
                            'icon'=>'bi bi-person-lock',
                        ],
                        [
                            'text'=>'Configurações',
                            'url'=>'configuracoes',
                            'icon'=>'bi bi-sliders',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
