@extends('enfas.layout')

@section('title','Visão geral')
@section('page_kicker','SIGH ENFAS · CENTRAL OPERACIONAL')
@section('page_title','Visão geral')
@section('page_subtitle','Agenda, pacientes, assistência e comunicação em uma única experiência.')

@section('page_actions')
<form method="GET" class="ea-unit-context">
    <i class="bi bi-buildings"></i>
    <select name="location_id" class="form-select" onchange="this.form.submit()">
        <option value="">Todas as unidades</option>
        @foreach($locations as $location)
            <option value="{{ $location->id }}" @selected((int)$locationId === (int)$location->id)>
                {{ $location->name }}
            </option>
        @endforeach
    </select>
</form>

@can('whatsapp.view')
<a href="{{ route('enfas.whatsapp') }}" class="btn btn-light border">
    <i class="bi bi-headset"></i>
    Central WhatsApp
    @if($inbox['unread'] > 0)
        <span class="badge text-bg-danger ms-1">{{ $inbox['unread'] }}</span>
    @endif
</a>
@endcan
@can('agenda.manage')
<a href="{{ route('agenda.index') }}" class="btn btn-primary">
    <i class="bi bi-calendar-plus"></i>Novo agendamento
</a>
@endcan
@endsection

@section('content')

<div class="ea-dashboard-context mb-4">
    <div>
        <span class="ea-dashboard-date">{{ now()->translatedFormat('l, d \d\e F') }}</span>
        <strong>Operação do dia</strong>
        <small>
            @if($locationId)
                {{ optional($locations->firstWhere('id',$locationId))->name }}
            @else
                Todas as unidades
            @endif
        </small>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @can('agenda.view')
        <a href="{{ route('agenda.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-calendar3 me-1"></i>Agenda
        </a>
        <a href="{{ route('waitlist.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-hourglass-split me-1"></i>Lista de espera
        </a>
        @endcan
        @can('reports.view')
        <a href="{{ route('v92.reports') }}" class="btn btn-outline-secondary">
            <i class="bi bi-bar-chart me-1"></i>Relatórios
        </a>
        @endcan
    </div>
</div>

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

@can('pharmacy.view')
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between gap-3">
        <div>
            <strong class="d-block">Assistência Farmacêutica</strong>
            <span class="small text-secondary">Medicamentos, PMC, LME e APAC que precisam de acompanhamento.</span>
        </div>
        <a href="{{ route('sigh.pharmacy.index') }}" class="btn btn-light border">
            Abrir módulo <i class="bi bi-arrow-up-right"></i>
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach([
                ['Medicamentos ativos',$care['medications'],'bi-capsule'],
                ['PMC em atenção',$care['pmc_attention'],'bi-house-heart'],
                ['LME pendentes',$care['lme_attention'],'bi-file-earmark-medical'],
                ['APAC em atenção',$care['apac_attention'],'bi-file-earmark-check'],
            ] as $item)
            <div class="col-6 col-xl-3">
                <div class="ea-mini-metric h-100">
                    <i class="bi {{ $item[2] }} mb-2 text-primary"></i>
                    <strong>{{ $item[1] }}</strong>
                    <span>{{ $item[0] }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endcan

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
                            <td><strong class="text-primary">{{ date('H:i', strtotime((string) $a->start_at)) }}</strong></td>
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

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <strong class="d-block">Experiência do paciente</strong>
                <span class="small text-secondary">Satisfação registrada nos últimos 30 dias</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="ea-week-metric">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span>NPS</span>
                            <strong>{{ $experience['nps'] === null ? '—' : $experience['nps'] }}</strong>
                            <small>recomendação do atendimento</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ea-week-metric">
                            <i class="bi bi-star-fill"></i>
                            <span>Estrelas</span>
                            <strong>{{ $experience['stars'] === null ? '—' : number_format($experience['stars'],1,',','.') }}</strong>
                            <small>média de 1 a 5</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ea-week-metric">
                            <i class="bi bi-chat-square-heart"></i>
                            <span>Respostas</span>
                            <strong>{{ $experience['responses'] }}</strong>
                            <small>avaliações recebidas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <strong class="d-block">Acesso rápido</strong>
                <span class="small text-secondary">Cadastros essenciais</span>
            </div>
            <div class="card-body v92-quick-list">
                <a href="{{ route('patients.index') }}"><i class="bi bi-people"></i>Pacientes</a>
                @can('pharmacy.view')
                <a href="{{ route('sigh.pharmacy.index') }}"><i class="bi bi-capsule"></i>Assistência Farmacêutica</a>
                @endcan
                <a href="{{ route('v9.locations.index') }}"><i class="bi bi-buildings"></i>Unidades</a>
                <a href="{{ route('services.index') }}"><i class="bi bi-grid"></i>Serviços</a>
                <a href="{{ route('v92.reports') }}"><i class="bi bi-bar-chart"></i>Relatórios</a>
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
