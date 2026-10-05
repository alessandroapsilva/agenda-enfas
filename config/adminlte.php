<?php

use ColorlibHQ\AdminLte\Menu\Filters\ActiveFilter;
use ColorlibHQ\AdminLte\Menu\Filters\GateFilter;
use ColorlibHQ\AdminLte\Menu\Filters\HrefFilter;
use ColorlibHQ\AdminLte\Menu\Filters\SearchFilter;

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | The default page title, and an optional prefix/postfix applied to every
    | page title set with @section('title', ...).
    |
    */

    'title' => 'ENFAS Agenda',
    'title_prefix' => '',
    'title_postfix' => ' | Enfermagem Alessandro Silva',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    */

    'use_ico_only' => false,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    |
    | AdminLTE 4 uses Source Sans 3. Set to false to self-host or skip.
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    |
    | The brand logo shown in the sidebar. `logo` accepts HTML and is
    | rendered UNESCAPED ({!! !!}) — only ever put trusted, hardcoded
    | markup here, never user-supplied or database-driven content.
    |
    */

    'logo' => '<strong>ENFAS</strong> <span class="opacity-75">Agenda</span>',
    'logo_img' => 'assets/brand/enfas-agenda.svg',
    'logo_img_class' => 'brand-image',
    'logo_img_alt' => 'ENFAS Agenda',

    /*
    |--------------------------------------------------------------------------
    | Authentication logo
    |--------------------------------------------------------------------------
    */

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'vendor/adminlte/img/AdminLTELogo.png',
            'alt' => 'Auth Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User menu (topbar dropdown)
    |--------------------------------------------------------------------------
    |
    | `usermenu_profile_url` is passed through `url()`, so set it to a path or
    | an absolute URL — not a route name. `adminlte:scaffold` prefixes its
    | routes with `admin`, so use 'admin/profile' once the profile section is
    | scaffolded. `false` hides the "Profile" button and lets "Sign out" fill
    | the footer.
    |
    */

    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'text-bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Body-level layout switches. These map directly to AdminLTE 4 body classes.
    |
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => true,   // .layout-fixed
    'layout_fixed_navbar' => true,    // .fixed-header
    'layout_fixed_footer' => null,    // .fixed-footer
    'layout_dark_mode' => null,       // null = respect system / user toggle
    'layout_rtl' => false,            // Enable right-to-left layout

    /*
    |--------------------------------------------------------------------------
    | Footer & Preloader
    |--------------------------------------------------------------------------
    |
    | `footer_left` / `footer_right` accept HTML and are rendered UNESCAPED
    | ({!! !!}) — only ever put trusted, hardcoded markup here, never
    | user-supplied or database-driven content.
    |
    */

    'footer_left' => '&copy; '.date('Y').' Enfermagem Alessandro Silva · Agenda ENFAS',
    'footer_right' => 'Ambiente interno seguro',
    'preloader' => false,
    'control_sidebar' => false,
    'control_sidebar_theme' => 'dark',

    // Documentation URL used by the navbar "Documentation" link and the sidebar
    // "View documentation" CTA (false to hide the CTA). Defaults to the in-app
    // docs viewer served at /docs (see the `docs` keys below).
    'sidebar_docs_url' => false,

    // Bundled demo/showcase pages (Dashboard v2/v3, Widgets, UI, Forms, Tables,
    // Layout Options, Theme Generate, auth variants, error pages). Set false to
    // skip registering their routes in production.
    'demo' => false,
    'demo_middleware' => ['web', 'auth'],

    // In-app documentation viewer: renders this package's docs/*.md files at
    // /docs and /docs/{page}. Set 'docs' => false to disable the route.
    'docs' => false,
    'docs_middleware' => ['web'],

    'sidebar_breakpoint' => 'lg',     // sidebar-expand-{breakpoint}
    'sidebar_mini' => true,           // .sidebar-mini
    'sidebar_collapse' => true,      // start collapsed
    'sidebar_collapse_auto_size' => false,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'leave',

    /*
    |--------------------------------------------------------------------------
    | Color theme
    |--------------------------------------------------------------------------
    |
    | The sidebar uses data-bs-theme="dark" by default (dark sidebar on a
    | light page, matching the AdminLTE 4 demos). Set to 'light' for a light
    | sidebar.
    |
    | The four *_color keys repaint the chrome without touching SCSS: each one
    | is injected into the layout <head> as a CSS custom-property override
    | (see ColorlibHQ\AdminLte\Support\ThemeColors). Leave a key null to keep
    | the stock AdminLTE colour. `primary_color` also recolours links and the
    | primary button variants. Values must be hex — '#rgb' or '#rrggbb';
    | anything else is ignored. The /demo/theme-generator page previews these
    | live and writes the snippet for you.
    |
    */

    'sidebar_theme' => 'dark',  // 'dark' | 'light'

    'primary_color' => '#2563eb',    // brand colour: links, .btn-primary, --bs-primary
    'sidebar_color' => '#0b1220',    // .app-sidebar background
    'navbar_color' => '#ffffff',     // .app-header background
    'footer_color' => '#ffffff',     // .app-footer background

    /*
    |--------------------------------------------------------------------------
    | Custom body / element classes
    |--------------------------------------------------------------------------
    */

    'classes_body' => 'enfas-shell',
    'classes_brand' => 'enfas-brandbar',
    'classes_brand_text' => 'fw-semibold',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'enfas-sidebar shadow',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-expand enfas-topbar',
    'classes_topnav_nav' => 'navbar',
    'classes_topnav_container' => 'container-fluid',

    /*
    |--------------------------------------------------------------------------
    | Color mode toggle
    |--------------------------------------------------------------------------
    |
    | Shows the Light/Dark/Auto dropdown in the topbar (AdminLTE 4 feature).
    |
    */

    'color_mode_toggle' => true,

    /*
    |--------------------------------------------------------------------------
    | Menu
    |--------------------------------------------------------------------------
    |
    | The sidebar (and optional top-nav) menu. Each item is an array. Supported
    | keys:
    |
    |   'header'      => 'SECTION LABEL'            // a section header
    |   'text'        => 'Dashboard'               // link label (required for links)
    |   'route'       => 'dashboard'               // named route  -> url
    |   'url'         => 'admin/users'             // raw url (relative or absolute)
    |   'icon'        => 'bi bi-speedometer'       // Bootstrap Icons class
    |   'icon_color'  => 'primary'                 // optional text-{color}
    |   'label'       => 5                         // badge value
    |   'label_color' => 'primary'                 // badge color
    |   'active'      => ['admin/users*']          // url patterns that mark active
    |   'target'      => '_blank'                  // anchor target
    |   'can'         => 'view-users'              // gate/permission to show item
    |   'submenu'     => [ ...child items... ]     // nested items (treeview)
    |
    */

    'menu' => [
        ['header' => 'OPERAÇÃO'],

        [
            'text' => 'Visão geral',
            'route' => 'dashboard',
            'icon' => 'bi bi-grid-1x2-fill',
            'can' => 'dashboard.view',
        ],
        [
            'text' => 'Meu painel',
            'route' => 'professional.workspace',
            'icon' => 'bi bi-person-workspace',
            'can' => 'professional.workspace',
        ],
        [
            'text' => 'Agenda',
            'route' => 'agenda.index',
            'icon' => 'bi bi-calendar3',
            'can' => 'agenda.view',
        ],
        [
            'text' => 'Agendamentos',
            'route' => 'appointments.index',
            'icon' => 'bi bi-calendar2-check',
            'can' => 'agenda.view',
        ],
        [
            'text' => 'Pacientes',
            'route' => 'patients.index',
            'icon' => 'bi bi-people',
            'can' => 'patients.view',
        ],
        [
            'text' => 'Lista de espera',
            'route' => 'waitlist.index',
            'icon' => 'bi bi-hourglass-split',
            'can' => 'agenda.view',
        ],
        [
            'text' => 'Confirmações',
            'route' => 'v11.confirmations',
            'icon' => 'bi bi-patch-check',
            'can' => 'agenda.view',
        ],

        ['header' => 'ATENDIMENTO'],

        [
            'text' => 'Central WhatsApp',
            'route' => 'enfas.whatsapp',
            'icon' => 'bi bi-whatsapp',
            'can' => 'whatsapp.view',
        ],
        [
            'text' => 'Templates',
            'route' => 'v9.templates.index',
            'icon' => 'bi bi-chat-square-text',
            'can' => 'whatsapp.view',
        ],
        [
            'text' => 'Automações',
            'route' => 'enfas.v6.automations',
            'icon' => 'bi bi-lightning-charge',
            'can' => 'whatsapp.manage',
        ],
        [
            'text' => 'Histórico de mensagens',
            'route' => 'enfas.v6.messages',
            'icon' => 'bi bi-send',
            'can' => 'whatsapp.view',
        ],

        ['header' => 'GESTÃO'],

        [
            'text' => 'Profissionais',
            'route' => 'professionals.index',
            'icon' => 'bi bi-person-badge',
            'can' => 'professionals.view',
        ],
        [
            'text' => 'Serviços',
            'route' => 'services.index',
            'icon' => 'bi bi-grid',
            'can' => 'services.view',
        ],
        [
            'text' => 'Unidades',
            'route' => 'v9.locations.index',
            'icon' => 'bi bi-buildings',
            'can' => 'professionals.view',
        ],
        [
            'text' => 'Indicadores e NPS',
            'route' => 'v92.reports',
            'icon' => 'bi bi-graph-up-arrow',
            'can' => 'reports.view',
        ],
        [
            'text' => 'Alertas operacionais',
            'route' => 'v92.alerts',
            'icon' => 'bi bi-bell',
            'can' => 'reports.view',
        ],

        ['header' => 'ADMINISTRAÇÃO'],

        [
            'text' => 'Usuários e acessos',
            'route' => 'users.index',
            'icon' => 'bi bi-person-lock',
            'can' => 'users.manage',
        ],
        [
            'text' => 'Auditoria',
            'route' => 'v92.audit',
            'icon' => 'bi bi-shield-check',
            'can' => 'audit.view',
        ],
        [
            'text' => 'Preferências',
            'route' => 'v92.settings',
            'icon' => 'bi bi-sliders2',
            'can' => 'settings.manage',
        ],
        [
            'text' => 'Campos personalizados',
            'route' => 'custom-fields.index',
            'icon' => 'bi bi-ui-checks-grid',
            'can' => 'settings.manage',
        ],
        [
            'text' => 'Saúde do sistema',
            'route' => 'v92.health.dashboard',
            'icon' => 'bi bi-heart-pulse',
            'can' => 'settings.manage',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu filters
    |--------------------------------------------------------------------------
    |
    | Filters transform each menu item before rendering. Add your own classes
    | here (must implement ColorlibHQ\AdminLte\Menu\Filters\FilterInterface).
    | The defaults handle gates, active state, hrefs, and search items.
    |
    */

    'filters' => [
        GateFilter::class,
        HrefFilter::class,
        ActiveFilter::class,
        SearchFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins
    |--------------------------------------------------------------------------
    |
    | Optional JavaScript libraries integrated with AdminLTE 4. Disable plugins
    | you don't use to avoid loading unnecessary assets.
    |
    */

    'plugins' => [
        'flatpickr' => [
            'enabled' => false,
            'css' => 'vendor/flatpickr/flatpickr.min.css',
            'js' => 'vendor/flatpickr/flatpickr.min.js',
        ],
        'tom_select' => [
            'enabled' => false,
            'css' => 'vendor/tom-select/tom-select.bootstrap5.min.css',
            'js' => 'vendor/tom-select/tom-select.complete.min.js',
        ],
        'tabulator' => [
            'enabled' => false,
            'css' => 'vendor/tabulator-tables/tabulator.min.css',
            'js' => 'vendor/tabulator-tables/tabulator.min.js',
        ],
        'quill' => [
            'enabled' => false,
            'css' => 'vendor/quill/quill.snow.css',
            'js' => 'vendor/quill/quill.min.js',
        ],
        'apexcharts' => [
            'enabled' => false,
            'js' => 'vendor/apexcharts/apexcharts.min.js',
        ],
        'jsvectormap' => [
            'enabled' => false,
            'css' => 'vendor/jsvectormap/jsvectormap.min.css',
            // The library first, then the world map data (registers the 'world' map).
            'js' => [
                'vendor/jsvectormap/jsvectormap.min.js',
                'vendor/jsvectormap/maps/world.js',
            ],
        ],
        'fullcalendar' => [
            'enabled' => false,
            'css' => 'vendor/fullcalendar/index.global.min.css',
            'js' => 'vendor/fullcalendar/index.global.min.js',
        ],
        'sortablejs' => [
            'enabled' => false,
            'js' => 'vendor/sortablejs/sortablejs.min.js',
        ],
    ],

];
