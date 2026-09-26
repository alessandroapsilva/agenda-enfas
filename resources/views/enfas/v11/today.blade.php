@extends('enfas.layout')

@section('title','Hoje • ENFAS Agenda')
@section('page_kicker','Agenda ENFAS')
@section('page_title','Hoje')
@section(
    'page_subtitle',
    'Horários, respostas e WhatsApp do dia em uma única tela.'
)

@section('page_actions')
<a
    href="{{ url('/agenda') }}"
    class="ea-btn-secondary"
>
    <i class="bi bi-calendar3"></i>
    Abrir agenda
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

    $appointmentMap = [
        'awaiting_confirmation' => [
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
        'completed' => [
            'Atendido',
            'confirmed',
        ],
        'no_show' => [
            'Não compareceu',
            'cancelled',
        ],
        'scheduled' => [
            'Agendado',
            'neutral',
        ],
    ];
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
            <i class="bi bi-calendar2-check"></i>
        </div>

        <div class="ea-metric-value">
            {{ $stats['total'] }}
        </div>

        <div class="ea-metric-label">
            Agendamentos
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
            <i class="bi bi-x-circle"></i>
        </div>

        <div class="ea-metric-value">
            {{ $stats['cancelled'] }}
        </div>

        <div class="ea-metric-label">
            Cancelados
        </div>
    </div>
</div>

<div class="ea-grid ea-grid-main">

    <div class="ea-card">
        <div class="ea-card-header">
            <div>
                <h2 class="ea-card-title">
                    Próximos horários
                </h2>

                <div class="ea-card-caption">
                    {{ $day->translatedFormat('l, d \d\e F') }}
                </div>
            </div>

            <a
                href="{{ route('v11.confirmations') }}"
                class="btn btn-sm btn-outline-secondary"
            >
                Ver confirmações
            </a>
        </div>

        @if($rows->isEmpty())
            <div class="ea-empty">
                <div class="ea-empty-icon">
                    <i class="bi bi-calendar2"></i>
                </div>

                <strong>
                    Nenhum horário para hoje
                </strong>

                A agenda do dia está livre.
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
                            <th>WhatsApp</th>
                            <th class="text-end">
                                Ações
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                    @foreach($rows as $row)
                        @php
                            $appointmentState =
                                strtolower(
                                    $row->status
                                    ?? ''
                                );

                            $appointmentUi =
                                $appointmentMap[
                                    $appointmentState
                                ]
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
                                ?? [
                                    'Não enviado',
                                    'neutral',
                                ];
                        @endphp

                        <tr>
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
                            </td>

                            <td>
                                <span class="ea-row-title">
                                    {{
                                        $row->patient_name
                                        ?: 'Paciente sem nome'
                                    }}
                                </span>

                                <span class="ea-row-meta">
                                    {{
                                        $row->patient_phone
                                        ?: 'Telefone não informado'
                                    }}
                                </span>
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
                                    class="ea-status {{ $appointmentUi[1] }}"
                                >
                                    {{ $appointmentUi[0] }}
                                </span>
                            </td>

                            <td>
                                <span
                                    class="ea-status {{ $waUi[1] }}"
                                    @if($row->wa_error)
                                        title="{{ $row->wa_error }}"
                                    @endif
                                >
                                    {{ $waUi[0] }}
                                </span>
                            </td>

                            <td class="ea-action-cell">
                                <div class="ea-actions">

                                    @if(
                                        ($row->confirmation_status ?? null)
                                            !== 'confirmed'
                                        && $row->status
                                            !== 'cancelled'
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
        @endif
    </div>

    <div class="ea-side-stack">

        <div class="ea-side-block">
            <h3 class="ea-side-title">
                WhatsApp · últimas 24h
            </h3>

            <div class="ea-side-row">
                <span>Enviados</span>
                <strong>
                    {{ $messages['sent'] }}
                </strong>
            </div>

            <div class="ea-side-row">
                <span>Entregues</span>
                <strong>
                    {{ $messages['delivered'] }}
                </strong>
            </div>

            <div class="ea-side-row">
                <span>Lidos</span>
                <strong>
                    {{ $messages['read'] }}
                </strong>
            </div>

            <div class="ea-side-row">
                <span>Falhas</span>
                <strong>
                    {{ $messages['failed'] }}
                </strong>
            </div>
        </div>

        <div class="ea-side-block">
            <h3 class="ea-side-title">
                Retorno do WhatsApp
            </h3>

            @if($lastWebhook)
                @if(
                    $lastWebhook->processed
                    && ! $lastWebhook->processing_error
                )
                    <div class="ea-health-ok">
                        Endpoint respondendo
                    </div>
                @else
                    <span class="ea-status failed">
                        Falha no processamento
                    </span>
                @endif

                <div class="ea-row-meta mt-2">
                    Último evento:
                    {{
                        \Illuminate\Support\Carbon::parse(
                            $lastWebhook->received_at
                            ?? $lastWebhook->created_at
                        )->format('d/m H:i:s')
                    }}
                </div>
            @else
                <span class="ea-status neutral">
                    Sem eventos recebidos
                </span>
            @endif
        </div>

        <div class="ea-side-block">
            <h3 class="ea-side-title">
                Automações
            </h3>

            <div class="ea-side-row">
                <span>Ativas</span>
                <strong>
                    {{ $automation['active'] }}
                </strong>
            </div>

            <div class="ea-side-row">
                <span>Configuradas</span>
                <strong>
                    {{ $automation['total'] }}
                </strong>
            </div>

            <a
                href="{{ url('/whatsapp/automacoes') }}"
                class="ea-btn-secondary w-100 mt-3"
            >
                <i class="bi bi-lightning-charge"></i>
                Abrir automações
            </a>
        </div>

        @if($failures->isNotEmpty())
            <div class="ea-side-block">
                <h3 class="ea-side-title">
                    Falhas recentes
                </h3>

                @foreach($failures as $failure)
                    <div class="ea-failure">
                        <strong>
                            {{
                                $failure->patient_name
                                ?: $failure->recipient
                            }}
                        </strong>

                        <span>
                            {{
                                $failure->error_message
                                ?: 'Envio não concluído.'
                            }}
                        </span>
                    </div>
                @endforeach

                <a
                    href="{{ url('/whatsapp/mensagens') }}"
                    class="ea-btn-secondary w-100 mt-3"
                >
                    Ver mensagens
                </a>
            </div>
        @endif
    </div>
</div>

@endsection
