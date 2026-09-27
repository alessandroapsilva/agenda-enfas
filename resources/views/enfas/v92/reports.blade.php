@extends('enfas.layout')

@section('title','Relatórios')
@section('page_kicker','GESTÃO E PERFORMANCE')
@section('page_title','Relatórios gerenciais')
@section('page_subtitle','Indicadores de agenda, atendimento, comunicação e lista de espera.')

@section('page_actions')
<a href="{{ route('v92.reports.export', ['from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')]) }}" class="btn btn-outline-primary">
    <i class="bi bi-download me-1"></i>Exportar CSV
</a>
@endsection

@section('content')

<form class="card mb-4" method="GET">
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">De</label>
            <input class="form-control" type="date" name="from" value="{{ $from->format('Y-m-d') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Até</label>
            <input class="form-control" type="date" name="to" value="{{ $to->format('Y-m-d') }}">
        </div>
        <div class="col-md-4">
            <button class="btn btn-primary w-100">
                <i class="bi bi-arrow-repeat me-1"></i>Atualizar período
            </button>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
@foreach([
    ['Agendamentos',$metrics['appointments'],'bi-calendar3'],
    ['Confirmados',$metrics['confirmed'],'bi-check2-circle'],
    ['Concluídos',$metrics['completed'],'bi-person-check'],
    ['Aguardando',$metrics['awaiting'],'bi-hourglass-split'],
    ['Cancelados',$metrics['cancelled'],'bi-x-circle'],
    ['Faltas',$metrics['no_show'],'bi-person-x'],
] as $m)
    <div class="col-6 col-md-4 col-xl-2">
        <div class="enfas-stat">
            <div class="enfas-stat-top">
                <span class="enfas-stat-label">{{ $m[0] }}</span>
                <span class="enfas-stat-icon"><i class="bi {{ $m[2] }}"></i></span>
            </div>
            <div class="enfas-stat-number">{{ $m[1] }}</div>
        </div>
    </div>
@endforeach
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['Taxa de confirmação',$rates['confirmation'],'bi-check2-circle'],
        ['Taxa de conclusão',$rates['completion'],'bi-clipboard2-check'],
        ['Taxa de cancelamento',$rates['cancellation'],'bi-calendar-x'],
        ['Taxa de falta',$rates['no_show'],'bi-person-x'],
    ] as $rate)
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-muted d-block">{{ $rate[0] }}</small>
                        <strong class="display-6">{{ number_format($rate[1],1,',','.') }}%</strong>
                    </div>
                    <i class="bi {{ $rate[2] }} fs-3 text-primary"></i>
                </div>
                <div class="progress mt-3" role="progressbar">
                    <div class="progress-bar" style="width: {{ min(100,$rate[1]) }}%"></div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <strong>Funil da jornada do paciente</strong>
                <div class="small text-muted">Confirmação, check-in, conclusão e satisfação no período.</div>
            </div>
            <div class="card-body">
                @php
                    $journeyBase = max(1, $metrics['appointments']);
                    $journeyStages = [
                        ['Confirmados',$journey['confirmed'],'bi-check2-circle'],
                        ['Check-ins',$journey['checkins'],'bi-qr-code-scan'],
                        ['Concluídos',$journey['completed'],'bi-person-check'],
                        ['Avaliações',$journey['responses'],'bi-chat-square-heart'],
                    ];
                @endphp

                <div class="row g-3">
                    @foreach($journeyStages as $stage)
                        <div class="col-md-3">
                            <div class="ea-week-metric">
                                <i class="bi {{ $stage[2] }}"></i>
                                <span>{{ $stage[0] }}</span>
                                <strong>{{ $stage[1] }}</strong>
                                <small>{{ number_format(($stage[1]/$journeyBase)*100,1,',','.') }}% dos agendamentos</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <strong>Satisfação do paciente</strong>
                <div class="small text-muted">NPS e nota média das respostas recebidas.</div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-4">
                        <div class="ea-mini-metric">
                            <strong>{{ $journey['nps'] === null ? '—' : $journey['nps'] }}</strong>
                            <span>NPS</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="ea-mini-metric">
                            <strong>{{ $journey['average_stars'] === null ? '—' : number_format($journey['average_stars'],1,',','.') }}</strong>
                            <span>Estrelas / 5</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="ea-mini-metric">
                            <strong>{{ $journey['responses'] }}</strong>
                            <span>Respostas</span>
                        </div>
                    </div>
                </div>

                @if($journey['average_stars'] !== null)
                    <div class="text-warning mt-3" aria-label="Média de estrelas">
                        @for($star=1;$star<=5;$star++)
                            <i class="bi {{ $star <= round($journey['average_stars']) ? 'bi-star-fill' : 'bi-star' }}"></i>
                        @endfor
                    </div>
                @endif

                <div class="small text-muted mt-2">
                    Média da pergunta NPS: {{ $journey['average_score'] === null ? '—' : number_format($journey['average_score'],1,',','.') }}/10.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <strong>Performance por profissional</strong>
                <div class="small text-muted">Volume, confirmações, conclusão, cancelamento e faltas no período.</div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Profissional</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Confirm.</th>
                            <th class="text-end">Concluídos</th>
                            <th class="text-end">Cancel.</th>
                            <th class="text-end">Faltas</th>
                            <th class="text-end">Taxa conf.</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($professionals as $row)
                        <tr>
                            <td>
                                <strong>{{ $row->name }}</strong>
                                <div class="small text-muted">{{ round($row->scheduled_minutes / 60,1) }} h agendadas</div>
                            </td>
                            <td class="text-end">{{ $row->total }}</td>
                            <td class="text-end">{{ $row->confirmed }}</td>
                            <td class="text-end">{{ $row->completed }}</td>
                            <td class="text-end">{{ $row->cancelled }}</td>
                            <td class="text-end">{{ $row->no_show }}</td>
                            <td class="text-end">
                                <span class="badge text-bg-light border">{{ number_format($row->confirmation_rate,1,',','.') }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted">Sem dados no período.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <strong>Serviços mais agendados</strong>
            </div>
            <div class="card-body">
                @forelse($services as $row)
                    <div class="border-bottom py-3">
                        <div class="d-flex justify-content-between gap-3">
                            <strong>{{ $row->name }}</strong>
                            <span class="badge text-bg-primary">{{ $row->total }}</span>
                        </div>
                        <div class="small text-muted mt-1">
                            {{ $row->completed }} concluídos ·
                            {{ $row->cancelled }} cancelados ·
                            {{ $row->no_show }} faltas
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Sem dados no período.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <strong>Tendência diária</strong>
                <div class="small text-muted">Movimento da agenda dentro do período selecionado.</div>
            </div>
            <div class="card-body">
                @php $maxDaily = max(1, (int) ($daily->max('total') ?? 1)); @endphp
                @forelse($daily as $row)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ \Carbon\Carbon::parse($row->day)->format('d/m') }}</span>
                            <strong>{{ $row->total }}</strong>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar" style="width:{{ ($row->total/$maxDaily)*100 }}%"></div>
                        </div>
                        <div class="small text-muted mt-1">
                            {{ $row->confirmed }} confirmados ·
                            {{ $row->completed }} concluídos ·
                            {{ $row->cancelled }} cancelados ·
                            {{ $row->no_show }} faltas
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Sem movimentação no período.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-3">
        <div class="card h-100">
            <div class="card-header">
                <strong>WhatsApp</strong>
            </div>
            <div class="card-body">
                @foreach([
                    'sent'=>'Enviadas',
                    'delivered'=>'Entregues',
                    'read'=>'Lidas',
                    'received'=>'Recebidas',
                    'failed'=>'Falhas'
                ] as $key=>$label)
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span>{{ $label }}</span>
                        <strong>{{ $communication[$key] }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-xl-3">
        <div class="card h-100">
            <div class="card-header">
                <strong>Lista de espera</strong>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Aguardando</span><strong>{{ $waitlist['waiting'] }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Oferta enviada</span><strong>{{ $waitlist['offered'] }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>Convertidos</span><strong>{{ $waitlist['accepted'] }}</strong>
                </div>

                <a href="{{ route('waitlist.index') }}" class="btn btn-outline-primary w-100 mt-3">
                    Abrir lista de espera
                </a>
            </div>
        </div>
    </div>
</div>

@if($cancellationReasons->isNotEmpty())
<div class="card mt-4">
    <div class="card-header">
        <strong>Motivos de cancelamento</strong>
        <div class="small text-muted">Principais motivos informados pelos pacientes no período.</div>
    </div>
    <div class="card-body">
        @php $maxReason = max(1, (int) $cancellationReasons->max('total')); @endphp
        @foreach($cancellationReasons as $reason)
            <div class="mb-3">
                <div class="d-flex justify-content-between gap-3 small mb-1">
                    <span class="text-truncate">{{ $reason->cancellation_reason }}</span>
                    <strong>{{ $reason->total }}</strong>
                </div>
                <div class="progress" style="height:7px;">
                    <div class="progress-bar bg-secondary" style="width:{{ ($reason->total/$maxReason)*100 }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

@stop
