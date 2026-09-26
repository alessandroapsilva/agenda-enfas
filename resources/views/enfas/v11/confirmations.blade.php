@extends('enfas.layout')

@section('title','Confirmações • ENFAS Agenda')
@section('page_kicker','Agenda ENFAS')
@section('page_title','Confirmações')
@section(
    'page_subtitle',
    'Trate respostas pendentes e acompanhe o estado do WhatsApp de cada horário.'
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
                        <th>WhatsApp</th>
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
                            === 'cancelled'
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
                            <span
                                class="ea-status {{ $waUi[1] }}"
                                @if($row->wa_error)
                                    title="{{ $row->wa_error }}"
                                @endif
                            >
                                {{ $waUi[0] }}
                            </span>

                            @if($row->wa_updated_at)
                                <span class="ea-row-meta">
                                    {{
                                        \Illuminate\Support\Carbon::parse(
                                            $row->wa_updated_at
                                        )->format('H:i')
                                    }}
                                </span>
                            @endif
                        </td>

                        <td class="ea-action-cell">
                            <div class="ea-actions">

                                @if(
                                    ($row->confirmation_status ?? null)
                                        !== 'confirmed'
                                    && $row->status
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
                                    $row->status
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
