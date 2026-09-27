@extends('enfas.layout')

@section('title','Visão geral')
@section('page_kicker','CENTRAL OPERACIONAL')
@section('page_title','Visão geral')
@section('page_subtitle','Agenda, confirmações e atendimento em uma única experiência.')

@section('page_actions')
<a href="{{ route('enfas.whatsapp') }}" class="btn btn-light border">
    <i class="bi bi-headset"></i>
    Central WhatsApp
    @if($inbox['unread'] > 0)
        <span class="badge text-bg-danger ms-1">{{ $inbox['unread'] }}</span>
    @endif
</a>
<a href="{{ url('/agenda') }}" class="btn btn-primary">
    <i class="bi bi-calendar-plus"></i>Novo agendamento
</a>
@endsection

@section('content')

<section class="v92-hero mb-4">
    <div>
        <span class="v92-eyebrow">ENFAS AGENDA · {{ now()->translatedFormat('d/m/Y') }}</span>
        <h2>Operação clínica sem ruído.</h2>
        <p>
            Acompanhe a agenda do dia, confirmações, conversas e indicadores
            com acesso rápido aos pontos que exigem atenção.
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ url('/agenda') }}" class="btn btn-light btn-lg">
            <i class="bi bi-calendar3"></i>Abrir agenda
        </a>
        <a href="{{ route('waitlist.index') }}" class="btn btn-outline-light btn-lg">
            <i class="bi bi-hourglass-split"></i>Lista de espera
        </a>
    </div>
</section>

<div class="row g-3 mb-4">
    @foreach([
        ['Agendamentos hoje',$metrics['today'],'bi-calendar3','Hoje'],
        ['Confirmados',$metrics['confirmed'],'bi-check2-circle','Presenças confirmadas'],
        ['Aguardando',$metrics['awaiting'],'bi-hourglass-split','Precisam de retorno'],
        ['Pacientes',$metrics['patients'],'bi-people','Base ativa'],
    ] as $card)
    <div class="col-xl-3 col-md-6">
        <div class="enfas-stat">
            <div class="enfas-stat-top">
                <span class="enfas-stat-label">{{ $card[0] }}</span>
                <span class="enfas-stat-icon"><i class="bi {{ $card[2] }}"></i></span>
            </div>
            <div class="enfas-stat-number">{{ $card[1] }}</div>
            <div class="enfas-stat-foot">{{ $card[3] }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center gap-3">
                <div>
                    <strong class="d-block">Operação de hoje</strong>
                    <span class="small text-secondary">Próximos atendimentos em ordem cronológica</span>
                </div>
                <a href="{{ url('/agenda') }}" class="btn btn-sm btn-light border">
                    Ver agenda completa <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Horário</th>
                            <th>Paciente</th>
                            <th>Atendimento</th>
                            <th>Profissional</th>
                            <th class="text-end">Status</th>
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
                            <td><strong class="text-primary">{{ CarbonCarbon::parse($a->start_at)->format('H:i') }}</strong></td>
                            <td><strong>{{ $a->patient_name }}</strong></td>
                            <td>{{ $a->service_name }}</td>
                            <td><span class="text-secondary">{{ $a->professional_name }}</span></td>
                            <td class="text-end"><span class="badge text-bg-{{ $class }}">{{ $label }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="enfas-empty">
                                    <div class="enfas-empty-icon"><i class="bi bi-calendar2-check"></i></div>
                                    <h5>Agenda tranquila</h5>
                                    <p>Nenhum atendimento programado para hoje.</p>
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
        <div class="card mb-4">
            <div class="card-header">
                <strong class="d-block">Central de Atendimento</strong>
                <span class="small text-secondary">Conversas WhatsApp em tempo real</span>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach([
                        ['Ativas',$inbox['active']],
                        ['Com equipe',$inbox['human']],
                        ['Não lidas',$inbox['unread']],
                    ] as $item)
                    <div class="col-4">
                        <div class="ea-mini-metric">
                            <strong>{{ $item[1] }}</strong>
                            <span>{{ $item[0] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                <a href="{{ route('enfas.whatsapp') }}" class="btn btn-success w-100 mt-3">
                    <i class="bi bi-whatsapp"></i>Abrir central
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong class="d-block">WhatsApp Business</strong>
                    <span class="small text-secondary">Meta Cloud API</span>
                </div>
                <span class="ea-status-pill {{ $wa['connected'] ? 'is-online' : 'is-warning' }}">
                    <span></span>{{ $wa['connected'] ? 'Conectado' : 'Atenção' }}
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="ea-wa-brand"><i class="bi bi-whatsapp"></i></div>
                    <div class="min-w-0">
                        <strong class="d-block text-truncate">{{ $wa['name'] ?: 'WhatsApp Business' }}</strong>
                        <span class="small text-secondary">Canal principal de comunicação</span>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-4"><div class="ea-mini-metric"><strong>{{ $wa['templates_approved'] }}</strong><span>Templates</span></div></div>
                    <div class="col-4"><div class="ea-mini-metric"><strong>{{ $wa['messages_today'] }}</strong><span>Hoje</span></div></div>
                    <div class="col-4"><div class="ea-mini-metric"><strong>{{ $wa['failed_today'] }}</strong><span>Falhas</span></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <strong class="d-block">Indicadores da semana</strong>
                <span class="small text-secondary">Resumo operacional dos agendamentos</span>
            </div>
            <div class="card-body">
                @php
                    $total = max(1,$week['total']);
                    $confirmedPct = round(($week['confirmed']/$total)*100);
                    $cancelledPct = round(($week['cancelled']/$total)*100);
                    $noShowPct = round(($week['no_show']/$total)*100);
                @endphp

                <div class="row g-3">
                    @foreach([
                        ['Total',$week['total'],null,'bi-calendar-week'],
                        ['Confirmados',$week['confirmed'],$confirmedPct.'%','bi-check-circle'],
                        ['Cancelados',$week['cancelled'],$cancelledPct.'%','bi-x-circle'],
                        ['Faltas',$week['no_show'],$noShowPct.'%','bi-person-x'],
                    ] as $item)
                    <div class="col-md-3">
                        <div class="ea-week-metric">
                            <i class="bi {{ $item[3] }}"></i>
                            <span>{{ $item[0] }}</span>
                            <strong>{{ $item[1] }}</strong>
                            @if($item[2])<small>{{ $item[2] }} do total</small>@endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong class="d-block">Alertas operacionais</strong>
                    <span class="small text-secondary">Pendências que precisam de atenção</span>
                </div>
                <a href="{{ url('/alertas') }}" class="btn btn-sm btn-light border"><i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($alerts as $alert)
                    <a href="{{ url('/alertas') }}" class="list-group-item list-group-item-action py-3">
                        <div class="d-flex gap-3">
                            <span class="ea-alert-dot"></span>
                            <div class="min-w-0">
                                <strong class="small d-block">{{ $alert->title }}</strong>
                                <span class="small text-secondary text-truncate d-block">{{ $alert->message }}</span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="enfas-empty" style="min-height:220px">
                        <div class="enfas-empty-icon"><i class="bi bi-check2-circle"></i></div>
                        <h5>Tudo em ordem</h5>
                        <p>Nenhuma pendência operacional agora.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@stop
