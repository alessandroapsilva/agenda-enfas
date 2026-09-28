@extends('layouts.app')

@section('title','Meu painel')
@section('page_kicker','ÁREA DO PROFISSIONAL')
@section('page_title','Meu dia')
@section('page_subtitle','Agenda própria, confirmações e próximos atendimentos em um só lugar.')

@section('content')

<div class="ea-professional-head mb-4">
    <div class="d-flex align-items-center gap-3 min-w-0">
        <div class="ea-avatar">{{ strtoupper(substr($professional->name,0,1)) }}</div>
        <div class="min-w-0">
            <span class="small text-secondary d-block">{{ now()->translatedFormat('l, d \d\e F') }}</span>
            <h4 class="mb-0 text-truncate">{{ $professional->name }}</h4>
            <span class="small text-secondary">{{ $professional->specialty ?: 'Profissional ENFAS' }}</span>
        </div>
    </div>
    <span class="badge text-bg-light border">
        <i class="bi bi-shield-check me-1"></i>Ambiente individual
    </span>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['Hoje',$metrics['today'],'bi-calendar3'],
        ['Confirmados',$metrics['confirmed'],'bi-check2-circle'],
        ['Concluídos',$metrics['completed'],'bi-person-check'],
        ['Aguardando',$metrics['pending'],'bi-hourglass-split'],
    ] as $card)
        <div class="col-6 col-xl-3">
            <div class="enfas-stat h-100">
                <div class="enfas-stat-top">
                    <span class="enfas-stat-label">{{ $card[0] }}</span>
                    <span class="enfas-stat-icon"><i class="bi {{ $card[2] }}"></i></span>
                </div>
                <div class="enfas-stat-number">{{ $card[1] }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <strong class="d-block">Atendimentos de hoje</strong>
                <span class="small text-secondary">Sua agenda em ordem cronológica</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Horário</th>
                            <th>Paciente</th>
                            <th>Serviço</th>
                            <th class="text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($today as $appointment)
                        <tr>
                            <td><strong class="text-primary">{{ $appointment->start_at->format('H:i') }}</strong></td>
                            <td>
                                <strong>{{ $appointment->patient?->preferred_name ?: $appointment->patient?->name }}</strong>
                                @if($appointment->check_in_completed_at)
                                    <div class="small text-success"><i class="bi bi-check-circle me-1"></i>Check-in realizado</div>
                                @endif
                            </td>
                            <td>{{ $appointment->service?->name }}</td>
                            <td class="text-end">
                                <span class="badge" style="background:{{ $appointment->statusColor() }}">{{ $appointment->statusLabel() }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="enfas-empty">
                                    <div class="enfas-empty-icon"><i class="bi bi-calendar2-check"></i></div>
                                    <h5>Sem atendimentos hoje</h5>
                                    <p>Sua agenda está livre neste momento.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <strong class="d-block">Próximos atendimentos</strong>
                <span class="small text-secondary">Até 12 próximos horários</span>
            </div>
            <div class="list-group list-group-flush">
                @forelse($upcoming as $appointment)
                    <div class="list-group-item py-3">
                        <div class="d-flex justify-content-between gap-3">
                            <div class="min-w-0">
                                <strong class="d-block text-truncate">{{ $appointment->patient?->preferred_name ?: $appointment->patient?->name }}</strong>
                                <span class="small text-secondary d-block text-truncate">{{ $appointment->service?->name }}</span>
                            </div>
                            <div class="text-end">
                                <strong class="d-block">{{ $appointment->start_at->format('d/m') }}</strong>
                                <span class="small text-secondary">{{ $appointment->start_at->format('H:i') }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="enfas-empty" style="min-height:260px">
                        <div class="enfas-empty-icon"><i class="bi bi-calendar-week"></i></div>
                        <h5>Nada pendente</h5>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@stop
