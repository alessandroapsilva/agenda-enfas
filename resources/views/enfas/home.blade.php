@extends('enfas.layout')

@section('title','Visão geral')
@section('page_kicker','AGENDA ENFAS')
@section('page_title','Visão geral')
@section('page_subtitle', now()->translatedFormat('l, d \d\e F \d\e Y'))

@section('page_actions')
<a href="{{ route('enfas.whatsapp') }}" class="btn btn-outline-success">
    <i class="bi bi-headset me-1"></i>Central de Atendimento
    @if($inbox['unread'] > 0)
        <span class="badge text-bg-danger ms-1">{{ $inbox['unread'] }}</span>
    @endif
</a>
<a href="{{ url('/agenda') }}" class="btn btn-primary">
    <i class="bi bi-calendar-plus me-1"></i>Novo agendamento
</a>
@endsection

@section('content')

<div class="row g-3 mb-4">
    @foreach([
        ['Agendamentos hoje',$metrics['today'],'bi-calendar3'],
        ['Confirmados',$metrics['confirmed'],'bi-check2-circle'],
        ['Aguardando',$metrics['awaiting'],'bi-hourglass-split'],
        ['Pacientes',$metrics['patients'],'bi-people'],
    ] as $card)
    <div class="col-xl-3 col-md-6">
        <div class="enfas-stat">
            <div class="enfas-stat-top">
                <span class="enfas-stat-label">{{ $card[0] }}</span>
                <span class="enfas-stat-icon"><i class="bi {{ $card[2] }}"></i></span>
            </div>
            <div class="enfas-stat-number">{{ $card[1] }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong>Operação de hoje</strong>
                    <div class="small text-secondary">Próximos atendimentos e situação da agenda.</div>
                </div>
                <a href="{{ url('/agenda') }}" class="btn btn-sm btn-outline-primary">Abrir agenda</a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Paciente</th>
                            <th>Atendimento</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($appointments as $a)
                        @php
                            $class = match($a->status) {
                                'confirmed' => 'success',
                                'completed' => 'primary',
                                'cancelled' => 'secondary',
                                'no_show' => 'danger',
                                default => 'warning'
                            };
                            $label = match($a->status) {
                                'confirmed' => 'Confirmado',
                                'completed' => 'Concluído',
                                'cancelled' => 'Cancelado',
                                'no_show' => 'Falta',
                                default => 'Aguardando'
                            };
                        @endphp
                        <tr>
                            <td><strong>{{ CarbonCarbon::parse($a->start_at)->format('H:i') }}</strong></td>
                            <td>{{ $a->patient_name }}</td>
                            <td>
                                <strong>{{ $a->service_name }}</strong>
                                <div class="small text-secondary">{{ $a->professional_name }}</div>
                            </td>
                            <td><span class="badge text-bg-{{ $class }}">{{ $label }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-5 text-muted">Nenhum atendimento programado para hoje.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header">
                <strong>Central de Atendimento</strong>
                <div class="small text-secondary">WhatsApp, robô e equipe.</div>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <div class="border rounded-3 p-3 text-center">
                            <strong class="d-block fs-4">{{ $inbox['active'] }}</strong>
                            <small class="text-muted">Ativas</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded-3 p-3 text-center">
                            <strong class="d-block fs-4">{{ $inbox['human'] }}</strong>
                            <small class="text-muted">Humanas</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded-3 p-3 text-center">
                            <strong class="d-block fs-4">{{ $inbox['unread'] }}</strong>
                            <small class="text-muted">Não lidas</small>
                        </div>
                    </div>
                </div>

                <a href="{{ route('enfas.whatsapp') }}" class="btn btn-success w-100">
                    <i class="bi bi-headset me-1"></i>Abrir central
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <strong>WhatsApp Business</strong>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ea-wa-brand"><i class="bi bi-whatsapp"></i></div>
                    <div>
                        <strong>{{ $wa['connected'] ? 'Conectado' : 'Requer atenção' }}</strong>
                        <div class="small text-muted">{{ $wa['name'] ?: 'Meta Cloud API' }}</div>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-4"><div class="border rounded-3 p-2 text-center"><strong class="d-block">{{ $wa['templates_approved'] }}</strong><small class="text-muted">Templates</small></div></div>
                    <div class="col-4"><div class="border rounded-3 p-2 text-center"><strong class="d-block">{{ $wa['messages_today'] }}</strong><small class="text-muted">Hoje</small></div></div>
                    <div class="col-4"><div class="border rounded-3 p-2 text-center"><strong class="d-block">{{ $wa['failed_today'] }}</strong><small class="text-muted">Falhas</small></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <strong>Indicadores da semana</strong>
                <div class="small text-secondary">Resumo operacional dos agendamentos.</div>
            </div>
            <div class="card-body">
                @php
                    $total = max(1,$week['total']);
                    $confirmedPct = round(($week['confirmed']/$total)*100);
                    $cancelledPct = round(($week['cancelled']/$total)*100);
                    $noShowPct = round(($week['no_show']/$total)*100);
                @endphp

                <div class="row g-3">
                    <div class="col-md-3"><div class="border rounded-3 p-3"><small class="text-muted d-block">Total</small><strong class="fs-3">{{ $week['total'] }}</strong></div></div>
                    <div class="col-md-3"><div class="border rounded-3 p-3"><small class="text-muted d-block">Confirmados</small><strong class="fs-3">{{ $week['confirmed'] }}</strong><div class="small text-success">{{ $confirmedPct }}%</div></div></div>
                    <div class="col-md-3"><div class="border rounded-3 p-3"><small class="text-muted d-block">Cancelados</small><strong class="fs-3">{{ $week['cancelled'] }}</strong><div class="small text-muted">{{ $cancelledPct }}%</div></div></div>
                    <div class="col-md-3"><div class="border rounded-3 p-3"><small class="text-muted d-block">Faltas</small><strong class="fs-3">{{ $week['no_show'] }}</strong><div class="small text-danger">{{ $noShowPct }}%</div></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <strong>Alertas operacionais</strong>
            </div>
            <div class="list-group list-group-flush">
                @forelse($alerts as $alert)
                    <a href="{{ url('/alertas') }}" class="list-group-item list-group-item-action">
                        <strong class="small">{{ $alert->title }}</strong>
                        <div class="small text-secondary text-truncate">{{ $alert->message }}</div>
                    </a>
                @empty
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-check2-circle fs-4 d-block mb-2"></i>
                        Nada pendente.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@stop
