<?php

return [
    'catalog' => [
        'dashboard.view' => 'Ver dashboard',
        'agenda.view' => 'Ver agenda',
        'agenda.manage' => 'Criar, mover e reagendar',
        'patients.view' => 'Ver pacientes',
        'patients.manage' => 'Cadastrar e editar pacientes',
        'professionals.view' => 'Ver profissionais',
        'professionals.manage' => 'Gerenciar profissionais e disponibilidade',
        'services.view' => 'Ver serviços',
        'services.manage' => 'Gerenciar serviços e orientações',
        'whatsapp.view' => 'Ver central WhatsApp',
        'whatsapp.manage' => 'Gerenciar mensagens, modelos e automações',
        'reports.view' => 'Ver relatórios',
        'users.manage' => 'Gerenciar usuários e acessos',
        'settings.manage' => 'Gerenciar configurações do sistema',
        'audit.view' => 'Ver auditoria',
    ],

    'roles' => [
        'admin' => ['*'],
        'supervisor' => [
            'dashboard.view','agenda.view','agenda.manage',
            'patients.view','patients.manage',
            'professionals.view','professionals.manage',
            'services.view','services.manage',
            'whatsapp.view','whatsapp.manage',
            'reports.view','audit.view',
        ],
        'professional' => [
            'dashboard.view','agenda.view',
            'patients.view','whatsapp.view',
        ],
        'attendant' => [
            'dashboard.view','agenda.view','agenda.manage',
            'patients.view','patients.manage',
            'professionals.view','services.view',
            'whatsapp.view',
        ],
    ],
];
