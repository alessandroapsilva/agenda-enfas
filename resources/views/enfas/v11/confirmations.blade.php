@extends('enfas.layout')

@section('title','Confirmações • ENFAS Agenda')
@section('page_kicker','Agenda ENFAS')
@section('page_title','Central de Confirmações')
@section(
    'page_subtitle',
    'Priorize respostas, acompanhe tentativas e trate exceções antes que virem faltas.'
)

@section('page_actions')
<a
    href="{{ route('v11.today') }}"
    class="ea-btn-secondary"
>
    <i class="bi bi-arrow-left"></i>
    Hoje
</a>

<a
    href="{{ url('/agenda') }}"
    class="ea-btn-main"
>
    <i class="bi bi-calendar3"></i>
    Abrir agenda
</a>
@endsection

@section('content')

@php
    $waMap = [
        'sent' => [
            'Enviado',
            'sent',
        ],
        'delivered' => [
            'Entregue',
            'delivered',
        ],
        'read' => [
            'Lido',
            'read',
        ],
        'failed' => [
            'Falhou',
            'failed',
        ],
        'received' => [
            'Recebido',
            'received',
        ],
        'queued' => [
            'Na fila',
            'neutral',
        ],
    ];

    $stateMap = [
        'waiting' => [
            'Aguardando',
            'waiting',
        ],
        'confirmed' => [
            'Confirmado',
            'confirmed',
        ],
        'cancelled' => [
            'Cancelado',
            'cancelled',
        ],
        'no_show' => [
            'Faltou',
            'failed',
        ],
    ];

    $voiceMap = [
        'queued' => ['Na fila', 'waiting'],
        'in_progress' => ['Em ligação', 'sent'],
        'completed' => ['Concluída', 'confirmed'],
    ];

    $voiceOutcomeMap = [
        'confirmed' => 'Confirmou',
        'cancelled' => 'Cancelou',
        'no_answer' => 'Não atendeu',
        'busy' => 'Ocupado',
        'invalid_number' => 'Número inválido',
        'callback' => 'Retorno solicitado',
        'no_input' => 'Sem resposta',
        'provider_failed' => 'Falha no provedor',
        'resolved_elsewhere' => 'Resolvido em outro canal',
    ];

    $performance = $insights['performance'];
    $risk = $insights['risk'];
    $whatsapp = $insights['whatsapp'];
    $voice = $insights['voice'];

    $waOperational =
        $whatsapp['available']
        && $whatsapp['active_automations'] > 0
        && $whatsapp['failed_24h'] === 0;

    $waAttention =
        $whatsapp['available']
        && $whatsapp['failed_24h'] > 0;

    $voiceReady =
        $voice['available']
        && $voice['configured']
        && $voice['webhook_validation'];

    $voiceLabel = $voice['enabled']
        ? (
            $voiceReady
                ? 'Ativo'
                : 'Configuração incompleta'
        )
        : (
            $voiceReady
                ? 'Pronto para ativar'
                : 'Modo seguro'
        );

    $voiceTone = $voice['enabled']
        ? (
            $voiceReady
                ? 'confirmed'
                : 'failed'
        )
        : 'neutral';
@endphp

@if(session('success'))
    <div class="ea-flash">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('success') }}
    </div>
@endif

<div class="ea-grid ea-grid-4 mb-3">
    <div class="ea-metric">
        <div class="ea-metric-icon">
            <i class="bi bi-calendar2"></i>
        </div>

        <div class="ea-metric-value">
            {{ $stats['total'] }}
        </div>

        <div class="ea-metric-label">
            Horários do dia
        </div>
    </div>

    <div class="ea-metric is-warning">
        <div class="ea-metric-icon">
            <i class="bi bi-hourglass-split"></i>
        </div>

        <div class="ea-metric-value">
            {{ $stats['waiting'] }}
        </div>

        <div class="ea-metric-label">
            Aguardando resposta
        </div>
    </div>

    <div class="ea-metric is-success">
        <div class="ea-metric-icon">
            <i class="bi bi-check2-circle"></i>
        </div>

        <div class="ea-metric-value">
            {{ $stats['confirmed'] }}
        </div>

        <div class="ea-metric-label">
            Confirmados
        </div>
    </div>

    <div class="ea-metric is-danger">
        <div class="ea-metric-icon">
            <i class="bi bi-exclamation-triangle"></i>
        </div>

        <div class="ea-metric-value">
            {{ $stats['attention'] ?? 0 }}
        </div>

        <div class="ea-metric-label">
            Precisam de atenção
        </div>
    </div>
</div>

<div class="ea-confirmation-cockpit mb-3">
    <section class="ea-ops-panel">
        <div class="ea-ops-panel-head">
            <div>
                <span class="ea-ops-eyebrow">
                    Performance
                </span>

                <h3>
                    Últimos 7 dias
                </h3>
            </div>

            <span class="ea-ops-period">
                {{
                    $insights['period']['start']->format('d/m')
                }}
                –
                {{
                    $insights['period']['end']->format('d/m')
                }}
            </span>
        </div>

        <div class="ea-ops-kpis">
            <div class="ea-ops-kpi">
                <strong>
                    {{ number_format($performance['confirmation_rate'], 1, ',', '.') }}%
                </strong>
                <span>Taxa de confirmação</span>
            </div>

            <div class="ea-ops-kpi">
                <strong>
                    {{ number_format($performance['resolution_rate'], 1, ',', '.') }}%
                </strong>
                <span>Horários resolvidos</span>
            </div>

            <div class="ea-ops-kpi">
                <strong>
                    {{ $performance['no_show_7d'] }}
                </strong>
                <span>Faltas registradas</span>
            </div>

            <div class="ea-ops-kpi">
                <strong>
                    {{
                        $performance['avg_lead_hours'] !== null
                            ? number_format(
                                $performance['avg_lead_hours'],
                                1,
                                ',',
                                '.'
                            ).'h'
                            : '—'
                    }}
                </strong>
                <span>Antecedência média</span>
            </div>
        </div>
    </section>

    <section class="ea-ops-panel">
        <div class="ea-ops-panel-head">
            <div>
                <span class="ea-ops-eyebrow">
                    SLA operacional
                </span>

                <h3>
                    Risco imediato
                </h3>
            </div>

            <span class="ea-status {{
                $risk['next_2h'] > 0
                    ? 'failed'
                    : 'confirmed'
            }}">
                {{
                    $risk['next_2h'] > 0
                        ? $risk['next_2h'].' urgente(s)'
                        : 'Sob controle'
                }}
            </span>
        </div>

        <div class="ea-risk-grid">
            <div class="ea-risk-item {{
                $risk['next_2h'] > 0
                    ? 'is-danger'
                    : ''
            }}">
                <i class="bi bi-alarm"></i>
                <div>
                    <strong>{{ $risk['next_2h'] }}</strong>
                    <span>sem resposta em até 2h</span>
                </div>
            </div>

            <div class="ea-risk-item">
                <i class="bi bi-clock-history"></i>
                <div>
                    <strong>{{ $risk['next_24h'] }}</strong>
                    <span>pendentes nas próximas 24h</span>
                </div>
            </div>

            <div class="ea-risk-item {{
                $risk['failed_contact'] > 0
                    ? 'is-danger'
                    : ''
            }}">
                <i class="bi bi-exclamation-octagon"></i>
                <div>
                    <strong>{{ $risk['failed_contact'] }}</strong>
                    <span>com falha de WhatsApp</span>
                </div>
            </div>

            <div class="ea-risk-item {{
                $risk['without_phone'] > 0
                    ? 'is-warning'
                    : ''
            }}">
                <i class="bi bi-telephone-x"></i>
                <div>
                    <strong>{{ $risk['without_phone'] }}</strong>
                    <span>sem telefone válido</span>
                </div>
            </div>
        </div>
    </section>

    <section class="ea-ops-panel ea-channel-health">
        <div class="ea-ops-panel-head">
            <div>
                <span class="ea-ops-eyebrow">
                    Canais
                </span>

                <h3>
                    Saúde operacional
                </h3>
            </div>
        </div>

        <div class="ea-channel-health-row">
            <div class="ea-channel-health-name">
                <span class="ea-channel-health-icon is-whatsapp">
                    <i class="bi bi-whatsapp"></i>
                </span>

                <div>
                    <strong>WhatsApp</strong>
                    <span>
                        {{ $whatsapp['active_automations'] }}
                        automações ativas
                    </span>
                </div>
            </div>

            <span class="ea-status {{
                $waAttention
                    ? 'failed'
                    : (
                        $waOperational
                            ? 'confirmed'
                            : 'neutral'
                    )
            }}">
                {{
                    $waAttention
                        ? 'Atenção'
                        : (
                            $waOperational
                                ? 'Operacional'
                                : 'Monitorando'
                        )
                }}
            </span>
        </div>

        <div class="ea-channel-health-meta">
            <span>
                <strong>{{ $whatsapp['outbound_7d'] }}</strong>
                envios / 7d
            </span>

            <span>
                <strong>{{ $whatsapp['read_7d'] }}</strong>
                leituras
            </span>

            <span>
                <strong>
                    {{ number_format($whatsapp['failure_rate'], 1, ',', '.') }}%
                </strong>
                falha
            </span>
        </div>

        <div class="ea-channel-health-row">
            <div class="ea-channel-health-name">
                <span class="ea-channel-health-icon is-voice">
                    <i class="bi bi-telephone"></i>
                </span>

                <div>
                    <strong>
                        Voz · {{ strtoupper($voice['provider']) }}
                    </strong>
                    <span>
                        {{
                            $voice['configured']
                                ? 'Credenciais presentes'
                                : 'Credenciais pendentes'
                        }}
                    </span>
                </div>
            </div>

            <span class="ea-status {{ $voiceTone }}">
                {{ $voiceLabel }}
            </span>
        </div>

        <div class="ea-channel-health-meta">
            <span>
                <strong>{{ $voice['queued'] }}</strong>
                na fila
            </span>

            <span>
                <strong>{{ $voice['attempts_7d'] }}</strong>
                tentativas / 7d
            </span>

            @if($voice['oldest_queue_at'])
                <span>
                    <strong>
                        {{
                            IlluminateSupportCarbon::parse(
                                $voice['oldest_queue_at']
                            )->diffForHumans(
                                now(),
                                true
                            )
                        }}
                    </strong>
                    fila mais antiga
                </span>
            @endif

            <span>
                <strong>
                    {{
                        $voice['webhook_validation']
                            ? 'ON'
                            : 'OFF'
                    }}
                </strong>
                assinatura
            </span>
        </div>
    </section>
</div>

<div class="ea-card">

    <form
        method="GET"
        action="{{ route('v11.confirmations') }}"
        class="ea-filterbar"
    >
        <div class="ea-field">
            <label for="ea-date">
                Data
            </label>

            <input
                id="ea-date"
                class="form-control"
                type="date"
                name="date"
                value="{{ $day->format('Y-m-d') }}"
            >
        </div>

        <div class="ea-field grow">
            <label for="ea-search">
                Paciente, telefone ou código
            </label>

            <input
                id="ea-search"
                data-ea-search
                class="form-control"
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Buscar..."
                autocomplete="off"
            >
        </div>

        <input
            type="hidden"
            name="state"
            value="{{ $state }}"
        >

        <button
            type="submit"
            class="btn btn-primary"
            style="min-height:39px"
        >
            <i class="bi bi-search"></i>
            Buscar
        </button>
    </form>

    <div
        class="d-flex align-items-center justify-content-between gap-3 flex-wrap px-3 py-3"
        style="border-bottom:1px solid var(--ea-border)"
    >
        <div class="ea-chips">

            <a
                class="ea-chip ea-chip-attention {{
                    $state === 'attention'
                        ? 'active'
                        : ''
                }}"
                href="{{
                    route(
                        'v11.confirmations',
                        array_filter([
                            'date' =>
                                $day->format('Y-m-d'),

                            'state' =>
                                'attention',

                            'q' =>
                                $search ?: null,
                        ])
                    )
                }}"
            >
                <i class="bi bi-exclamation-triangle me-1"></i>
                Atenção
                <strong class="ms-1">
                    {{ $stats['attention'] ?? 0 }}
                </strong>
            </a>

            <a
                class="ea-chip {{
                    $state === 'calls'
                        ? 'active'
                        : ''
                }}"
                href="{{
                    route(
                        'v11.confirmations',
                        array_filter([
                            'date' => $day->format('Y-m-d'),
                            'state' => 'calls',
                            'q' => $search ?: null,
                        ])
                    )
                }}"
            >
                <i class="bi bi-telephone-outbound me-1"></i>
                Ligações
                <strong class="ms-1">
                    {{ $stats['calls'] ?? 0 }}
                </strong>
            </a>

            <a
                class="ea-chip {{
                    $state === 'waiting'
                        ? 'active'
                        : ''
                }}"
                href="{{
                    route(
                        'v11.confirmations',
                        array_filter([
                            'date' =>
                                $day->format('Y-m-d'),

                            'state' =>
                                'waiting',

                            'q' =>
                                $search ?: null,
                        ])
                    )
                }}"
            >
                Aguardando
                <strong class="ms-1">
                    {{ $stats['waiting'] }}
                </strong>
            </a>

            <a
                class="ea-chip {{
                    $state === 'confirmed'
                        ? 'active'
                        : ''
                }}"
                href="{{
                    route(
                        'v11.confirmations',
                        array_filter([
                            'date' =>
                                $day->format('Y-m-d'),

                            'state' =>
                                'confirmed',

                            'q' =>
                                $search ?: null,
                        ])
                    )
                }}"
            >
                Confirmados
                <strong class="ms-1">
                    {{ $stats['confirmed'] }}
                </strong>
            </a>

            <a
                class="ea-chip {{
                    $state === 'cancelled'
                        ? 'active'
                        : ''
                }}"
                href="{{
                    route(
                        'v11.confirmations',
                        array_filter([
                            'date' =>
                                $day->format('Y-m-d'),

                            'state' =>
                                'cancelled',

                            'q' =>
                                $search ?: null,
                        ])
                    )
                }}"
            >
                Cancelados
                <strong class="ms-1">
                    {{ $stats['cancelled'] }}
                </strong>
            </a>

            <a
                class="ea-chip {{
                    $state === 'no_show'
                        ? 'active'
                        : ''
                }}"
                href="{{
                    route(
                        'v11.confirmations',
                        array_filter([
                            'date' =>
                                $day->format('Y-m-d'),

                            'state' =>
                                'no_show',

                            'q' =>
                                $search ?: null,
                        ])
                    )
                }}"
            >
                Faltas
                <strong class="ms-1">
                    {{ $stats['no_show'] ?? 0 }}
                </strong>
            </a>

            <a
                class="ea-chip {{
                    $state === 'all'
                        ? 'active'
                        : ''
                }}"
                href="{{
                    route(
                        'v11.confirmations',
                        array_filter([
                            'date' =>
                                $day->format('Y-m-d'),

                            'state' =>
                                'all',

                            'q' =>
                                $search ?: null,
                        ])
                    )
                }}"
            >
                Todos
            </a>
        </div>

        <span class="ea-row-meta m-0">
            {{ $day->translatedFormat('d \d\e F \d\e Y') }}
        </span>
    </div>

    @if($rows->isEmpty())
        <div class="ea-empty">
            <div class="ea-empty-icon">
                <i class="bi bi-check2-circle"></i>
            </div>

            <strong>
                Nenhum horário nesta seleção
            </strong>

            Ajuste a data ou os filtros acima.
        </div>
    @else
        <div class="table-responsive">
            <table class="ea-table">
                <thead>
                    <tr>
                        <th>Horário</th>
                        <th>Paciente</th>
                        <th>Atendimento</th>
                        <th>Resposta</th>
                        <th>Contato automático</th>
                        <th class="text-end">
                            Ações
                        </th>
                    </tr>
                </thead>

                <tbody>
                @foreach($rows as $row)
                    @php
                        if (
                            $row->status
                            === 'no_show'
                        ) {
                            $responseUi =
                                $stateMap[
                                    'no_show'
                                ];
                        } elseif (
                            in_array(
                                $row->status,
                                ['cancelled','canceled'],
                                true
                            )
                        ) {
                            $responseUi =
                                $stateMap[
                                    'cancelled'
                                ];
                        } elseif (
                            ($row->confirmation_status ?? null)
                            === 'confirmed'
                            || $row->status
                            === 'confirmed'
                        ) {
                            $responseUi =
                                $stateMap[
                                    'confirmed'
                                ];
                        } else {
                            $responseUi =
                                $stateMap[
                                    'waiting'
                                ];
                        }

                        $waState =
                            strtolower(
                                $row->wa_status
                                ?? ''
                            );

                        $waUi =
                            $waMap[$waState]
                            ?? [
                                'Não enviado',
                                'neutral',
                            ];

                        $startAt =
                            \Illuminate\Support\Carbon::parse(
                                $row->start_at
                            );

                        $minutesUntil =
                            now()->diffInMinutes(
                                $startAt,
                                false
                            );

                        $isWaiting =
                            $responseUi[1]
                            === 'waiting';

                        $voiceState =
                            strtolower(
                                $row->voice_status
                                ?? ''
                            );

                        $voiceUi =
                            $voiceMap[$voiceState]
                            ?? ['Sem fila', 'neutral'];

                        $voiceOutcome =
                            $voiceOutcomeMap[
                                $row->voice_outcome
                                ?? ''
                            ]
                            ?? null;

                        $priorityUi = null;

                        if (
                            $isWaiting
                            && $waState === 'failed'
                        ) {
                            $priorityUi = [
                                'Falha no contato',
                                'danger',
                            ];
                        } elseif (
                            $isWaiting
                            && $minutesUntil >= 0
                            && $minutesUntil <= 120
                        ) {
                            $priorityUi = [
                                'Urgente',
                                'danger',
                            ];
                        } elseif (
                            $isWaiting
                            && $minutesUntil >= 0
                            && $minutesUntil <= 1440
                        ) {
                            $priorityUi = [
                                'Prioridade',
                                'warning',
                            ];
                        } elseif (
                            $isWaiting
                            && (int) ($row->wa_attempts ?? 0) === 0
                        ) {
                            $priorityUi = [
                                'Sem contato',
                                'warning',
                            ];
                        }
                    @endphp

                    <tr class="ea-confirmation-row {{ $priorityUi ? 'is-attention' : '' }}">
                        <td>
                            <span class="ea-time">
                                {{
                                    \Illuminate\Support\Carbon::parse(
                                        $row->start_at
                                    )->format('H:i')
                                }}
                            </span>

                            <span class="ea-code">
                                {{ $row->code }}
                            </span>

                            @if($priorityUi)
                                <span class="ea-confirmation-priority {{ $priorityUi[1] }}">
                                    {{ $priorityUi[0] }}
                                </span>
                            @endif
                        </td>

                        <td>
                            <span class="ea-row-title">
                                {{
                                    $row->patient_name
                                    ?: 'Paciente sem nome'
                                }}
                            </span>

                            @if($row->patient_phone)
                                <a
                                    class="ea-row-meta text-decoration-none"
                                    href="tel:{{ preg_replace('/\\D+/', '', $row->patient_phone) }}"
                                >
                                    <i class="bi bi-telephone me-1"></i>
                                    {{ $row->patient_phone }}
                                </a>
                            @else
                                <span class="ea-row-meta">
                                    Telefone não informado
                                </span>
                            @endif
                        </td>

                        <td>
                            <span class="ea-row-title">
                                {{
                                    $row->service_name
                                    ?: 'Atendimento'
                                }}
                            </span>

                            <span class="ea-row-meta">
                                {{
                                    $row->professional_name
                                    ?: 'Profissional não definido'
                                }}

                                @if($row->location_name)
                                    · {{ $row->location_name }}
                                @endif
                            </span>
                        </td>

                        <td>
                            <span
                                class="ea-status {{ $responseUi[1] }}"
                            >
                                {{ $responseUi[0] }}
                            </span>

                            @if($row->confirmed_at)
                                <span class="ea-row-meta">
                                    {{
                                        \Illuminate\Support\Carbon::parse(
                                            $row->confirmed_at
                                        )->format('d/m H:i')
                                    }}
                                </span>
                            @endif
                        </td>

                        <td>
                            <div class="ea-contact-stack">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="ea-channel-pill">
                                        <i class="bi bi-whatsapp"></i>
                                        WhatsApp
                                    </span>

                                    <span
                                        class="ea-status {{ $waUi[1] }}"
                                        @if($row->wa_error)
                                            title="{{ $row->wa_error }}"
                                        @endif
                                    >
                                        {{ $waUi[0] }}
                                    </span>
                                </div>

                                <span class="ea-contact-meta">
                                    {{ (int) ($row->wa_attempts ?? 0) }}
                                    {{ (int) ($row->wa_attempts ?? 0) === 1 ? 'tentativa' : 'tentativas' }}

                                    @if($row->wa_last_outbound_at)
                                        · última
                                        {{
                                            \Illuminate\Support\Carbon::parse(
                                                $row->wa_last_outbound_at
                                            )->format('H:i')
                                        }}
                                    @endif
                                </span>

                                @if($row->wa_last_inbound_at)
                                    <span class="ea-contact-meta is-positive">
                                        <i class="bi bi-reply-fill"></i>
                                        Resposta recebida
                                        {{
                                            \Illuminate\Support\Carbon::parse(
                                                $row->wa_last_inbound_at
                                            )->format('H:i')
                                        }}
                                    </span>
                                @endif

                                @if($row->wa_error)
                                    <span
                                        class="ea-contact-error"
                                        title="{{ $row->wa_error }}"
                                    >
                                        {{ \Illuminate\Support\Str::limit($row->wa_error, 74) }}
                                    </span>
                                @endif

                                <div class="ea-contact-divider"></div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="ea-channel-pill is-voice">
                                        <i class="bi bi-telephone"></i>
                                        Ligação
                                    </span>

                                    <span class="ea-status {{ $voiceUi[1] }}">
                                        {{ $voiceOutcome ?: $voiceUi[0] }}
                                    </span>
                                </div>

                                @if((int) ($row->voice_attempts ?? 0) > 0)
                                    <span class="ea-contact-meta">
                                        {{ (int) $row->voice_attempts }}
                                        {{
                                            (int) $row->voice_attempts === 1
                                                ? 'tentativa'
                                                : 'tentativas'
                                        }}

                                        @if($row->voice_scheduled_at)
                                            · fila
                                            {{
                                                \Illuminate\Support\Carbon::parse(
                                                    $row->voice_scheduled_at
                                                )->format('H:i')
                                            }}
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="ea-action-cell">
                            <div class="ea-actions">

                                @if($row->patient_phone)
                                    <a
                                        href="tel:{{ preg_replace('/\\D+/', '', $row->patient_phone) }}"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Ligar para o paciente"
                                    >
                                        <i class="bi bi-telephone"></i>
                                    </a>

                                    @if(in_array($voiceState, ['queued','in_progress'], true))
                                        <form
                                            method="POST"
                                            action="{{
                                                route(
                                                    'v11.confirmations.call',
                                                    $row->id
                                                )
                                            }}"
                                            class="ea-call-outcome-form"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <select
                                                name="outcome"
                                                class="form-select form-select-sm"
                                                required
                                                title="Resultado da ligação"
                                            >
                                                <option value="">Resultado...</option>
                                                <option value="confirmed">Confirmou</option>
                                                <option value="no_answer">Não atendeu</option>
                                                <option value="busy">Ocupado</option>
                                                <option value="callback">Pediu retorno</option>
                                                <option value="invalid_number">Número inválido</option>
                                                <option value="cancelled">Cancelou</option>
                                            </select>

                                            <button
                                                class="btn btn-sm btn-outline-primary"
                                                type="submit"
                                            >
                                                Registrar
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                <a
                                    href="{{ url('/whatsapp/mensagens?appointment_id='.$row->id) }}"
                                    class="btn btn-sm btn-outline-secondary"
                                    title="Abrir histórico de mensagens"
                                >
                                    <i class="bi bi-whatsapp"></i>
                                </a>

                                @if(
                                    ($row->confirmation_status ?? null)
                                        !== 'confirmed'
                                    && ! in_array(
                                        $row->status,
                                        [
                                            'confirmed',
                                            'cancelled',
                                            'canceled',
                                            'no_show',
                                        ],
                                        true
                                    )
                                )
                                    <form
                                        method="POST"
                                        action="{{
                                            route(
                                                'v11.confirmations.mark',
                                                $row->id
                                            )
                                        }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="confirm"
                                        >

                                        <button
                                            class="btn btn-sm btn-primary"
                                            type="submit"
                                        >
                                            <i class="bi bi-check2"></i>
                                            Confirmar
                                        </button>
                                    </form>
                                @endif

                                <a
                                    href="{{
                                        url(
                                            '/agenda?agendamento='
                                            .$row->id
                                        )
                                    }}"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    <i class="bi bi-calendar-event"></i>
                                    Reagendar
                                </a>

                                @if(
                                    ! in_array(
                                        $row->status,
                                        [
                                            'cancelled',
                                            'canceled',
                                            'no_show',
                                        ],
                                        true
                                    )
                                )
                                    <form
                                        method="POST"
                                        action="{{
                                            route(
                                                'v11.confirmations.mark',
                                                $row->id
                                            )
                                        }}"
                                        data-ea-confirm="Cancelar este horário?"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="cancel"
                                        >

                                        <button
                                            class="btn btn-sm btn-outline-danger"
                                            type="submit"
                                        >
                                            <i class="bi bi-x-lg"></i>
                                            Cancelar
                                        </button>
                                    </form>
                                @else
                                    <form
                                        method="POST"
                                        action="{{
                                            route(
                                                'v11.confirmations.mark',
                                                $row->id
                                            )
                                        }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="pending"
                                        >

                                        <button
                                            class="btn btn-sm btn-outline-secondary"
                                            type="submit"
                                        >
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            Reabrir
                                        </button>
                                    </form>
                                @endif

                                <a
                                    href="{{
                                        url(
                                            '/agendamentos/'
                                            .$row->id
                                        )
                                    }}"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    <i class="bi bi-arrow-up-right"></i>
                                    Abrir
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($rows->hasPages())
            <div class="ea-pagination">
                <div class="ea-pagination-info">
                    {{
                        $rows->firstItem()
                    }}
                    –
                    {{
                        $rows->lastItem()
                    }}
                    de
                    {{
                        $rows->total()
                    }}
                </div>

                <div class="ea-pagination-actions">
                    @if($rows->onFirstPage())
                        <button
                            class="btn btn-sm btn-outline-secondary"
                            disabled
                        >
                            Anterior
                        </button>
                    @else
                        <a
                            class="btn btn-sm btn-outline-secondary"
                            href="{{ $rows->previousPageUrl() }}"
                        >
                            Anterior
                        </a>
                    @endif

                    @if($rows->hasMorePages())
                        <a
                            class="btn btn-sm btn-outline-secondary"
                            href="{{ $rows->nextPageUrl() }}"
                        >
                            Próxima
                        </a>
                    @else
                        <button
                            class="btn btn-sm btn-outline-secondary"
                            disabled
                        >
                            Próxima
                        </button>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>

@endsection
