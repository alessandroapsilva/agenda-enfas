@extends('layouts.app')

@section('title', 'Visão geral')

@section('content_header')

<div class="d-flex flex-wrap align-items-end justify-content-between gap-3">

    <div>

        <h3 class="enfas-page-title">
            Visão geral
        </h3>

        <p class="enfas-page-subtitle">

            {{ now()->translatedFormat('l, d \d\e F') }}

        </p>

    </div>

    <a href="{{ route('agenda.index') }}"
       class="btn btn-primary">

        <i class="bi bi-plus-lg me-1"></i>

        Novo agendamento

    </a>

</div>

@stop


@section('content')

<div class="row g-3 mb-4">

    @php
        $cards = [
            [
                'label' => 'Agendamentos hoje',
                'value' => $metrics['today'],
                'icon' => 'bi-calendar3',
                'foot' => 'Programados para hoje',
            ],
            [
                'label' => 'Confirmados',
                'value' => $metrics['confirmed'],
                'icon' => 'bi-check2-circle',
                'foot' => 'Presenças confirmadas',
            ],
            [
                'label' => 'Aguardando',
                'value' => $metrics['awaiting'],
                'icon' => 'bi-clock-history',
                'foot' => 'Aguardando confirmação',
            ],
            [
                'label' => 'Cancelados',
                'value' => $metrics['cancelled'],
                'icon' => 'bi-x-circle',
                'foot' => 'Cancelamentos de hoje',
            ],
        ];
    @endphp

    @foreach($cards as $card)

        <div class="col-xl-3 col-md-6">

            <div class="enfas-stat">

                <div class="enfas-stat-top">

                    <span class="enfas-stat-label">
                        {{ $card['label'] }}
                    </span>

                    <span class="enfas-stat-icon">
                        <i class="bi {{ $card['icon'] }}"></i>
                    </span>

                </div>

                <div class="enfas-stat-number">
                    {{ $card['value'] }}
                </div>

                <div class="enfas-stat-foot">
                    {{ $card['foot'] }}
                </div>

            </div>

        </div>

    @endforeach

</div>


<div class="row g-3">

    <div class="col-xl-8">

        <div class="card">

            <div class="card-header py-3">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <h6 class="mb-0 fw-semibold">
                            Próximos de hoje
                        </h6>

                        <small class="text-secondary">
                            Operação da agenda
                        </small>
                    </div>

                    <a href="{{ route('agenda.index') }}"
                       class="btn btn-sm btn-light">

                        Abrir agenda

                    </a>

                </div>

            </div>

            <div class="card-body p-0">

                @forelse($upcoming as $appointment)

                    <div class="enfas-upcoming-row">

                        <div class="enfas-time">
                            {{ $appointment->start_at->format('H:i') }}
                        </div>

                        <div class="flex-grow-1">

                            <strong>
                                {{ $appointment->patient->name }}
                            </strong>

                            <div class="small text-secondary">
                                {{ $appointment->service->name }}
                                ·
                                {{ $appointment->professional->name }}
                            </div>

                        </div>

                        <span class="badge text-bg-{{ $appointment->statusBadge() }}">

                            {{ $appointment->statusLabel() }}

                        </span>

                    </div>

                @empty

                    <div class="enfas-empty">

                        <div class="enfas-empty-icon">
                            <i class="bi bi-calendar2"></i>
                        </div>

                        <h5>Agenda livre</h5>

                        <p>
                            Nenhum agendamento programado para hoje.
                        </p>

                    </div>

                @endforelse

            </div>

        </div>

    </div>


    <div class="col-xl-4">

        <div class="card h-100">

            <div class="card-header py-3">

                <h6 class="mb-0 fw-semibold">
                    Central de confirmação
                </h6>

                <small class="text-secondary">
                    WhatsApp Business Platform
                </small>

            </div>

            <div class="card-body">

                <div class="enfas-empty">

                    <div class="enfas-empty-icon">
                        <i class="bi bi-whatsapp"></i>
                    </div>

                    <h5>Pronto para a Meta</h5>

                    <p>
                        O núcleo de confirmação já está preparado.
                        A próxima etapa será conectar a Cloud API.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

@stop
