@extends('enfas.layout')

@section('title','Visão geral')
@section('page_title','Visão geral')
@section('page_subtitle','Confirmações e agenda em uma visão operacional.')

@section('content')
<div class="cc-hero mb-4">
    <div>
        <span class="cc-kicker">CENTRAL DE CONFIRMAÇÕES</span>
        <h2>Veja quem confirmou, quem cancelou e quem ainda precisa responder.</h2>
        <p>
            Operação focada em reduzir faltas e manter a agenda organizada.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ url('/confirmacoes') }}" class="btn btn-primary btn-lg">
            <i class="bi bi-check2-circle me-2"></i>
            Abrir confirmações
        </a>

        <a href="{{ url('/agenda') }}" class="btn btn-light btn-lg">
            <i class="bi bi-calendar3 me-2"></i>
            Abrir agenda
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
@foreach([
    ['Hoje',$metrics['today'],'bi-calendar-event','primary'],
    ['Aguardando',$metrics['pending'],'bi-hourglass-split','warning'],
    ['Confirmados',$metrics['confirmed'],'bi-check2-circle','success'],
    ['Cancelados',$metrics['cancelled'],'bi-x-circle','danger'],
    ['Mensagens hoje',$metrics['messages'],'bi-send','info'],
    ['Profissionais',$metrics['professionals'],'bi-person-badge','secondary'],
] as $m)
<div class="col-6 col-md-4 col-xl-2">
    <div class="cc-stat">
        <div class="cc-stat-icon text-bg-{{ $m[3] }}">
            <i class="bi {{ $m[2] }}"></i>
        </div>
        <strong>{{ $m[1] }}</strong>
        <span>{{ $m[0] }}</span>
    </div>
</div>
@endforeach
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card cc-card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <div>
                    <strong>Próximos agendamentos</strong>
                    <div class="small text-secondary">Atalhos para a operação do dia.</div>
                </div>

                <a href="{{ url('/confirmacoes') }}" class="btn btn-sm btn-outline-primary">
                    Ver todos
                </a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Atendimento</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($upcoming as $row)
                        @php
                            $status = filled($row->cancelled_at??null)
                                ? ['Cancelado','danger']
                                : (filled($row->confirmed_at??null)
                                    ? ['Confirmado','success']
                                    : ['Aguardando','warning']);
                            $dateValue=$dateColumn ? ($row->{$dateColumn}??null) : null;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $row->patient_name }}</strong>
                                <div class="small text-secondary">{{ $row->patient_phone }}</div>
                            </td>
                            <td>
                                {{ $row->service_name }}
                                <div class="small text-secondary">{{ $row->professional_name }}</div>
                            </td>
                            <td>
                                {{ $dateValue ? \Illuminate\Support\Carbon::parse($dateValue)->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td>
                                <span class="badge text-bg-{{ $status[1] }}">{{ $status[0] }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ url('/agendamentos/'.$row->id) }}" class="btn btn-sm btn-light">
                                    Abrir
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-5">Nenhum agendamento futuro.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card cc-card mb-4">
            <div class="card-header border-0">
                <strong>Operação rápida</strong>
            </div>

            <div class="card-body cc-actions">
                <a href="{{ url('/confirmacoes?status=pending') }}">
                    <i class="bi bi-hourglass-split"></i>
                    <span>Aguardando resposta</span>
                    <strong>{{ $metrics['pending'] }}</strong>
                </a>
                <a href="{{ url('/whatsapp/automacoes') }}">
                    <i class="bi bi-lightning-charge"></i>
                    <span>Regras automáticas</span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
                <a href="{{ url('/whatsapp/templates') }}">
                    <i class="bi bi-chat-square-text"></i>
                    <span>Modelos de mensagem</span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
                <a href="{{ url('/comunicacoes') }}">
                    <i class="bi bi-clock-history"></i>
                    <span>Histórico de contatos</span>
                    <i class="bi bi-chevron-right ms-auto"></i>
                </a>
            </div>
        </div>

        <div class="card cc-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-secondary">Saúde do sistema</div>
                        <strong>{{ $healthSummary['total']-$healthSummary['failed'] }}/{{ $healthSummary['total'] }}</strong>
                    </div>
                    <span class="cc-health {{ $healthSummary['ok']?'is-ok':'is-bad' }}"></span>
                </div>

                <a href="{{ url('/sistema/saude') }}" class="btn btn-outline-primary w-100 mt-3">
                    Ver diagnóstico
                </a>
            </div>
        </div>
    </div>
</div>
@stop
