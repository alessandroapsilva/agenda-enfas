@extends('enfas.layout')

@section('title','Hoje • ENFAS Agenda')
@section('page_kicker','Operação diária')
@section('page_title','Hoje')
@section(
    'page_subtitle',
    'Agenda, confirmações e comunicação do dia em uma visão única.'
)

@section('page_actions')
<a
    href="{{ url('/agenda') }}"
    class="ea-btn-secondary"
>
    <i class="bi bi-calendar3"></i>
    Ver agenda
</a>

<a
    href="{{ url('/agendamentos') }}"
    class="ea-btn-main"
>
    <i class="bi bi-plus-lg"></i>
    Novo agendamento
</a>
@endsection

@section('content')

@php
    $waMap = [
        'sent' => ['Enviado', 'sent'],
        'delivered' => ['Entregue', 'delivered'],
        'read' => ['Lido', 'read'],
        'failed' => ['Falhou', 'failed'],
        'received' => ['Recebido', 'received'],
        'queued' => ['Na fila', 'neutral'],
    ];

    $appointmentMap = [
        'awaiting_confirmation' => ['Aguardando', 'waiting'],
        'confirmed' => ['Confirmado', 'confirmed'],
        'cancelled' => ['Cancelado', 'cancelled'],
        'canceled' => ['Cancelado', 'cancelled'],
        'completed' => ['Atendido', 'confirmed'],
        'no_show' => ['Não compareceu', 'failed'],
        'scheduled' => ['Agendado', 'neutral'],
    ];

    $resolved =
        (int) $stats['confirmed']
        + (int) $stats['cancelled'];

    $resolutionRate =
        (int) $stats['total'] > 0
            ? round(
                ($resolved / (int) $stats['total']) * 100
            )
            : 0;

    $whatsAppHealthy =
        (int) $messages['failed'] === 0;

    $automationHealthy =
        (int) $automation['active'] > 0;

    $dayLabel =
        IlluminateSupportStr::ucfirst(
            $day->translatedFormat('l, d \d\e F')
        );
@endphp

@if(session('success'))
    <div class="ea-flash">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('success') }}
    </div>
@endif

<div class="ea-today-hero">
    <div class="ea-today-hero-copy">
        <div class="ea-today-date">
            <span class="ea-today-date-icon">
                <i class="bi bi-calendar3"></i>
            </span>

            <div>
                <span>Agenda do dia</span>
                <strong>{{ $dayLabel }}</strong>
            </div>
        </div>

        <div class="ea-today-summary">
            <span>
                <i class="bi bi-circle-fill is-success"></i>
                Operação online
            </span>

            <span>
                <i class="bi bi-whatsapp"></i>
                {{
                    $whatsAppHealthy
                        ? 'WhatsApp sem falhas nas últimas 24h'
                        : $messages['failed'].' falha(s) de WhatsApp'
                }}
            </span>
        </div>
    </div>

    <div class="ea-today-progress">
        <div class="ea-today-progress-copy">
            <span>Resolução do dia</span>
            <strong>{{ $resolutionRate }}%</strong>
        </div>

        <div class="ea-progress-track">
            <span style="width: {{ min(100, $resolutionRate) }}%"></span>
        </div>
    </div>
</div>

<div class="ea-today-kpis">
    <article class="ea-kpi-card">
        <div class="ea-kpi-icon is-blue">
            <i class="bi bi-calendar2-week"></i>
        </div>

        <div class="ea-kpi-copy">
            <span>Agendamentos</span>
            <strong>{{ $stats['total'] }}</strong>
            <small>Total previsto para hoje</small>
        </div>
    </article>

    <article class="ea-kpi-card">
        <div class="ea-kpi-icon is-amber">
            <i class="bi bi-hourglass-split"></i>
        </div>

        <div class="ea-kpi-copy">
            <span>Aguardando</span>
            <strong>{{ $stats['waiting'] }}</strong>
            <small>Pendentes de confirmação</small>
        </div>
    </article>

    <article class="ea-kpi-card">
        <div class="ea-kpi-icon is-green">
            <i class="bi bi-patch-check"></i>
        </div>

        <div class="ea-kpi-copy">
            <span>Confirmados</span>
            <strong>{{ $stats['confirmed'] }}</strong>
            <small>Presenças confirmadas</small>
        </div>
    </article>

    <article class="ea-kpi-card">
        <div class="ea-kpi-icon is-red">
            <i class="bi bi-x-circle"></i>
        </div>

        <div class="ea-kpi-copy">
            <span>Cancelados</span>
            <strong>{{ $stats['cancelled'] }}</strong>
            <small>Horários liberados no dia</small>
        </div>
    </article>
</div>

<div class="ea-today-layout">

    <section class="ea-saas-card ea-today-agenda">
        <div class="ea-saas-card-head">
            <div>
                <span class="ea-section-kicker">
                    Agenda operacional
                </span>

                <h2>Próximos horários</h2>

                <p>
                    Paciente, atendimento, confirmação e comunicação no mesmo fluxo.
                </p>
            </div>

            <a
                href="{{ route('v11.confirmations') }}"
                class="ea-btn-ghost"
            >
                Central de confirmações
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        @if($rows->isEmpty())
            <div class="ea-premium-empty">
                <span class="ea-premium-empty-icon">
                    <i class="bi bi-calendar2-check"></i>
                </span>

                <div>
                    <strong>Agenda livre hoje</strong>
                    <p>
                        Nenhum atendimento está previsto para este dia.
                    </p>
                </div>

                <a
                    href="{{ url('/agendamentos') }}"
                    class="ea-btn-secondary"
                >
                    <i class="bi bi-plus-lg"></i>
                    Criar agendamento
                </a>
            </div>
        @else
            <div class="ea-appointment-list">
                @foreach($rows as $row)
                    @php
                        $appointmentState =
                            strtolower(
                                $row->status
                                ?? ''
                            );

                        $appointmentUi =
                            $appointmentMap[$appointmentState]
                            ?? [
                                ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $appointmentState
                                    )
                                ),
                                'neutral',
                            ];

                        if (
                            ($row->confirmation_status ?? null)
                            === 'confirmed'
                        ) {
                            $appointmentUi = [
                                'Confirmado',
                                'confirmed',
                            ];
                        }

                        $waState =
                            strtolower(
                                $row->wa_status
                                ?? ''
                            );

                        $waUi =
                            $waMap[$waState]
                            ?? ['Não enviado', 'neutral'];

                        $startAt =
                            IlluminateSupportCarbon::parse(
                                $row->start_at
                            );

                        $contactBlocked =
                            (bool) ($row->do_not_contact ?? false)
                            || (
                                isset($row->contact_consent)
                                && ! (bool) $row->contact_consent
                            );
                    @endphp

                    <article class="ea-appointment-row">
                        <div class="ea-appointment-time">
                            <strong>
                                {{ $startAt->format('H:i') }}
                            </strong>

                            <span>
                                {{ $row->code }}
                            </span>
                        </div>

                        <div class="ea-appointment-person">
                            <div class="ea-avatar">
                                {{
                                    IlluminateSupportStr::upper(
                                        IlluminateSupportStr::substr(
                                            $row->patient_name
                                                ?: 'P',
                                            0,
                                            1
                                        )
                                    )
                                }}
                            </div>

                            <div>
                                <strong>
                                    {{
                                        $row->patient_name
                                        ?: 'Paciente sem nome'
                                    }}
                                </strong>

                                <span>
                                    @if($contactBlocked)
                                        <i class="bi bi-shield-x"></i>
                                        Não contatar
                                    @elseif($row->patient_phone)
                                        <i class="bi bi-telephone"></i>
                                        {{ $row->patient_phone }}
                                    @else
                                        Telefone não informado
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div class="ea-appointment-service">
                            <strong>
                                {{
                                    $row->service_name
                                    ?: 'Atendimento'
                                }}
                            </strong>

                            <span>
                                {{
                                    $row->professional_name
                                    ?: 'Profissional não definido'
                                }}

                                @if($row->location_name)
                                    · {{ $row->location_name }}
                                @endif
                            </span>
                        </div>

                        <div class="ea-appointment-channel">
                            <span class="ea-status {{ $appointmentUi[1] }}">
                                {{ $appointmentUi[0] }}
                            </span>

                            <span
                                class="ea-wa-mini {{ $waUi[1] }}"
                                @if($row->wa_error)
                                    title="{{ $row->wa_error }}"
                                @endif
                            >
                                <i class="bi bi-whatsapp"></i>
                                {{ $waUi[0] }}
                            </span>
                        </div>

                        <div class="ea-appointment-actions">
                            @if(
                                ($row->confirmation_status ?? null)
                                    !== 'confirmed'
                                && ! in_array(
                                    $row->status,
                                    [
                                        'confirmed',
                                        'cancelled',
                                        'canceled',
                                        'completed',
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
                                        class="ea-icon-action is-primary"
                                        type="submit"
                                        title="Confirmar presença"
                                    >
                                        <i class="bi bi-check2"></i>
                                    </button>
                                </form>
                            @endif

                            <a
                                href="{{
                                    route(
                                        'appointments.show',
                                        $row->id
                                    )
                                }}"
                                class="ea-icon-action"
                                title="Abrir agendamento"
                            >
                                <i class="bi bi-arrow-up-right"></i>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <aside class="ea-today-side">

        <section class="ea-saas-card ea-health-card">
            <div class="ea-saas-card-head is-compact">
                <div>
                    <span class="ea-section-kicker">
                        Comunicação
                    </span>

                    <h2>Saúde do WhatsApp</h2>
                </div>

                <span class="ea-health-dot {{
                    $whatsAppHealthy
                        ? 'is-online'
                        : 'is-danger'
                }}"></span>
            </div>

            <div class="ea-health-grid">
                <div>
                    <span>Enviados</span>
                    <strong>{{ $messages['sent'] }}</strong>
                </div>

                <div>
                    <span>Entregues</span>
                    <strong>{{ $messages['delivered'] }}</strong>
                </div>

                <div>
                    <span>Lidos</span>
                    <strong>{{ $messages['read'] }}</strong>
                </div>

                <div class="{{
                    $messages['failed'] > 0
                        ? 'is-danger'
                        : ''
                }}">
                    <span>Falhas</span>
                    <strong>{{ $messages['failed'] }}</strong>
                </div>
            </div>

            <a
                href="{{ route('enfas.v6.messages') }}"
                class="ea-inline-link"
            >
                Abrir histórico
                <i class="bi bi-arrow-right"></i>
            </a>
        </section>

        <section class="ea-saas-card">
            <div class="ea-saas-card-head is-compact">
                <div>
                    <span class="ea-section-kicker">
                        Infraestrutura
                    </span>

                    <h2>Automação</h2>
                </div>

                <span class="ea-status {{
                    $automationHealthy
                        ? 'confirmed'
                        : 'neutral'
                }}">
                    {{
                        $automationHealthy
                            ? 'Operacional'
                            : 'Sem regras ativas'
                    }}
                </span>
            </div>

            <div class="ea-automation-summary">
                <div>
                    <span>Ativas</span>
                    <strong>{{ $automation['active'] }}</strong>
                </div>

                <div>
                    <span>Configuradas</span>
                    <strong>{{ $automation['total'] }}</strong>
                </div>
            </div>

            @if($lastWebhook)
                <div class="ea-system-row">
                    <span class="ea-system-icon {{
                        $lastWebhook->processed
                        && ! $lastWebhook->processing_error
                            ? 'is-online'
                            : 'is-danger'
                    }}">
                        <i class="bi bi-broadcast-pin"></i>
                    </span>

                    <div>
                        <strong>
                            {{
                                $lastWebhook->processed
                                && ! $lastWebhook->processing_error
                                    ? 'Webhook respondendo'
                                    : 'Webhook requer atenção'
                            }}
                        </strong>

                        <span>
                            Último evento
                            {{
                                IlluminateSupportCarbon::parse(
                                    $lastWebhook->received_at
                                    ?? $lastWebhook->created_at
                                )->format('H:i:s')
                            }}
                        </span>
                    </div>
                </div>
            @else
                <div class="ea-system-row">
                    <span class="ea-system-icon">
                        <i class="bi bi-broadcast"></i>
                    </span>

                    <div>
                        <strong>Sem eventos recentes</strong>
                        <span>Aguardando retorno do webhook.</span>
                    </div>
                </div>
            @endif
        </section>

        @if($failures->isNotEmpty())
            <section class="ea-saas-card is-danger-soft">
                <div class="ea-saas-card-head is-compact">
                    <div>
                        <span class="ea-section-kicker">
                            Atenção
                        </span>

                        <h2>Falhas recentes</h2>
                    </div>

                    <span class="ea-danger-count">
                        {{ $failures->count() }}
                    </span>
                </div>

                <div class="ea-failure-list">
                    @foreach($failures->take(3) as $failure)
                        <div class="ea-failure-item">
                            <strong>
                                {{
                                    $failure->patient_name
                                    ?: $failure->recipient
                                }}
                            </strong>

                            <span>
                                {{
                                    IlluminateSupportStr::limit(
                                        $failure->error_message
                                            ?: 'Envio não concluído.',
                                        70
                                    )
                                }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <a
                    href="{{ route('enfas.v6.messages') }}"
                    class="ea-inline-link is-danger"
                >
                    Ver falhas
                    <i class="bi bi-arrow-right"></i>
                </a>
            </section>
        @endif
    </aside>
</div>

@endsection
