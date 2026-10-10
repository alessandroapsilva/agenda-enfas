@extends('layouts.app')

@section('title', 'Central de Relacionamento')
@section('page_kicker', 'Jornada do paciente')
@section('page_title', 'Central de Relacionamento')
@section('page_subtitle', 'Confirmações, comunicação, lista de espera e jornada do paciente em um único cockpit operacional.')

@section('page_actions')
    @can('agenda.manage')
        <a href="{{ route('appointments.index') }}" class="btn btn-primary">
            <i class="bi bi-calendar-plus me-1"></i> Novo agendamento
        </a>
    @endcan
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Agendamentos hoje', 'value' => $metrics['today'], 'icon' => 'bi-calendar3'],
            ['label' => 'Aguardando confirmação', 'value' => $metrics['pending'], 'icon' => 'bi-hourglass-split'],
            ['label' => 'Confirmados', 'value' => $metrics['confirmed'], 'icon' => 'bi-patch-check'],
            ['label' => 'Lista de espera ativa', 'value' => $metrics['waitlist'], 'icon' => 'bi-people'],
            ['label' => 'Mensagens hoje', 'value' => $metrics['messages_today'], 'icon' => 'bi-chat-dots'],
            ['label' => 'Cancelados hoje', 'value' => $metrics['cancelled'], 'icon' => 'bi-calendar-x'],
        ] as $card)
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-body-tertiary d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                            <i class="bi {{ $card['icon'] }} fs-4"></i>
                        </div>
                        <div>
                            <div class="text-body-secondary small">{{ $card['label'] }}</div>
                            <div class="fs-3 fw-semibold lh-sm">{{ number_format($card['value'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h2 class="h5 mb-1">Precisam de atenção</h2>
                            <p class="text-body-secondary mb-0">Pacientes de hoje ainda aguardando confirmação.</p>
                        </div>
                        <a href="{{ route('v11.confirmations') }}" class="btn btn-sm btn-outline-primary">
                            Abrir central
                        </a>
                    </div>
                </div>
                <div class="card-body px-0 pb-0">
                    @if($attention->isEmpty())
                        <div class="text-center text-body-secondary py-5 px-4">
                            <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>
                            Nenhuma confirmação pendente para hoje.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Horário</th>
                                        <th>Paciente</th>
                                        <th>Atendimento</th>
                                        <th>Profissional</th>
                                        <th class="text-end pe-4">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($attention as $row)
                                    <tr>
                                        <td class="ps-4 fw-semibold">{{ \Illuminate\Support\Carbon::parse($row->start_at)->format('H:i') }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $row->patient_name ?: 'Paciente' }}</div>
                                            <div class="small text-body-secondary">{{ $row->patient_phone ?: 'Sem telefone' }}</div>
                                        </td>
                                        <td>{{ $row->service_name ?: 'Atendimento' }}</td>
                                        <td>{{ $row->professional_name ?: '—' }}</td>
                                        <td class="text-end pe-4">
                                            <a href="{{ route('v11.confirmations', ['q' => $row->code ?: $row->patient_name]) }}" class="btn btn-sm btn-outline-secondary">
                                                Tratar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h2 class="h5 mb-1">Fluxo operacional</h2>
                    <p class="text-body-secondary mb-0">Acesso rápido aos pontos da jornada.</p>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('v11.confirmations') }}" class="btn btn-outline-primary text-start">
                        <i class="bi bi-patch-check me-2"></i> Central de confirmações
                    </a>
                    @can('whatsapp.view')
                        <a href="{{ route('enfas.whatsapp') }}" class="btn btn-outline-secondary text-start">
                            <i class="bi bi-whatsapp me-2"></i> Conversas WhatsApp
                        </a>
                        <a href="{{ route('enfas.v6.messages') }}" class="btn btn-outline-secondary text-start">
                            <i class="bi bi-clock-history me-2"></i> Histórico de comunicação
                        </a>
                    @endcan
                    <a href="{{ route('waitlist.index') }}" class="btn btn-outline-secondary text-start">
                        <i class="bi bi-hourglass-split me-2"></i> Lista de espera
                    </a>
                    @can('whatsapp.manage')
                        <a href="{{ route('enfas.v6.automations') }}" class="btn btn-outline-secondary text-start">
                            <i class="bi bi-lightning-charge me-2"></i> Regras automáticas
                        </a>
                    @endcan
                    @can('reports.view')
                        <a href="{{ route('v92.reports') }}" class="btn btn-outline-secondary text-start">
                            <i class="bi bi-graph-up-arrow me-2"></i> Indicadores
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-lg-8">
                    <span class="badge text-bg-primary mb-2">ENFAS Jornada</span>
                    <h2 class="h4 mb-2">Da marcação ao pós-atendimento</h2>
                    <p class="text-body-secondary mb-0">
                        A central passa a ser a camada operacional que conecta agenda, confirmação, WhatsApp,
                        reagendamento, fila de espera, check-in, prontuário e acompanhamento pós-atendimento.
                    </p>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="small text-body-secondary mb-2">Próximas evoluções do hub</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge rounded-pill text-bg-light border">Jornadas visuais</span>
                        <span class="badge rounded-pill text-bg-light border">SLA de contato</span>
                        <span class="badge rounded-pill text-bg-light border">Omnichannel</span>
                        <span class="badge rounded-pill text-bg-light border">Recuperação de faltas</span>
                        <span class="badge rounded-pill text-bg-light border">Campanhas</span>
                        <span class="badge rounded-pill text-bg-light border">BI de conversão</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
