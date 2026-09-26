@extends('enfas.layout')

@section('title','Central')
@section('page_title','Central ENFAS')
@section('page_subtitle','Operação clínica, comunicação e gestão em uma única experiência.')

@section('content')
<div class="v92-hero mb-4">
    <div>
        <span class="v92-eyebrow">CENTRAL DE OPERAÇÕES</span>
        <h2>Seu dia inteiro em uma única tela.</h2>
        <p>
            Agenda, equipe, pacientes, WhatsApp, relatórios e saúde do sistema
            com acesso rápido e visual corporativo.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ url('/agenda') }}" class="btn btn-primary btn-lg">
            <i class="bi bi-calendar-plus me-2"></i>
            Abrir agenda
        </a>

        <a href="{{ url('/whatsapp/templates?novo=1') }}" class="btn btn-light btn-lg">
            <i class="bi bi-chat-square-text me-2"></i>
            Novo modelo
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
@foreach([
    ['Atendimentos hoje',$metrics['today'],'bi-calendar2-check'],
    ['Pacientes',$metrics['patients'],'bi-people'],
    ['Profissionais',$metrics['professionals'],'bi-person-badge'],
    ['Serviços',$metrics['services'],'bi-grid'],
    ['Templates ativos',$metrics['approved'],'bi-whatsapp'],
    ['Mensagens hoje',$metrics['messages'],'bi-send'],
] as $m)
    <div class="col-6 col-md-4 col-xl-2">
        <div class="v92-stat">
            <i class="bi {{ $m[2] }}"></i>
            <strong>{{ $m[1] }}</strong>
            <span>{{ $m[0] }}</span>
        </div>
    </div>
@endforeach
</div>

<div class="row g-4">
    <div class="col-xl-9">
        @foreach([
            'Atendimento'=>[
                ['/agenda','Agenda','Visão diária e movimentação','bi-calendar3'],
                ['/agendamentos','Agendamentos','Histórico e status','bi-calendar2-check'],
                ['/disponibilidade','Disponibilidade','Horários, férias e bloqueios','bi-clock-history'],
            ],
            'Cadastros'=>[
                ['/profissionais','Profissionais','Equipe e registros','bi-person-badge'],
                ['/pacientes','Pacientes','Cadastro e comunicação','bi-people'],
                ['/servicos','Serviços','Duração, valor e preparo','bi-grid'],
                ['/locais','Unidades','Locais de atendimento','bi-geo-alt'],
                ['/campos-personalizados','Campos personalizados','Dados sob medida','bi-ui-checks-grid'],
            ],
            'WhatsApp'=>[
                ['/whatsapp','Central WhatsApp','Conexão e operação','bi-whatsapp'],
                ['/whatsapp/templates','Modelos','Criar, editar e revisar','bi-chat-square-text'],
                ['/whatsapp/automacoes','Automações','Confirmações e lembretes','bi-lightning-charge'],
                ['/whatsapp/mensagens','Mensagens','Fila e histórico','bi-send'],
                ['/whatsapp/midia','Mídia','Imagens dos modelos','bi-images'],
            ],
            'Gestão'=>[
                ['/relatorios','Relatórios','Indicadores gerenciais','bi-bar-chart'],
                ['/auditoria','Auditoria','Rastreabilidade','bi-shield-check'],
                ['/alertas','Alertas','Pendências da operação','bi-bell'],
                ['/usuarios','Usuários','Acesso e segurança','bi-person-lock'],
                ['/configuracoes','Configurações','Preferências operacionais','bi-sliders'],
                ['/sistema/saude','Saúde do sistema','Go/No-Go e infraestrutura','bi-heart-pulse'],
            ],
        ] as $group=>$items)
        <section class="mb-4">
            <h6 class="v92-section-title">{{ $group }}</h6>

            <div class="row g-3">
            @foreach($items as $item)
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="{{ url($item[0]) }}" class="v92-module">
                        <div class="v92-module-icon">
                            <i class="bi {{ $item[3] }}"></i>
                        </div>

                        <div class="min-w-0">
                            <strong>{{ $item[1] }}</strong>
                            <span>{{ $item[2] }}</span>
                        </div>

                        <i class="bi bi-arrow-up-right ms-auto opacity-50"></i>
                    </a>
                </div>
            @endforeach
            </div>
        </section>
        @endforeach
    </div>

    <div class="col-xl-3">
        <div class="v92-side-card mb-3">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <strong>Saúde do sistema</strong>
                <span class="v92-health-dot {{ $healthSummary['ok']?'is-ok':'is-bad' }}"></span>
            </div>

            <div class="display-6 fw-bold">
                {{ $healthSummary['total']-$healthSummary['failed'] }}/{{ $healthSummary['total'] }}
            </div>

            <div class="text-secondary small mb-3">
                verificações operacionais aprovadas
            </div>

            <a href="{{ url('/sistema/saude') }}" class="btn btn-outline-primary w-100">
                Ver diagnóstico
            </a>
        </div>

        <div class="v92-side-card">
            <strong>Ações rápidas</strong>

            <div class="v92-quick-list mt-3">
                <a href="{{ url('/profissionais') }}">
                    <i class="bi bi-person-plus"></i> Cadastrar profissional
                </a>
                <a href="{{ url('/pacientes') }}">
                    <i class="bi bi-person-vcard"></i> Cadastrar paciente
                </a>
                <a href="{{ url('/whatsapp/templates?novo=1') }}">
                    <i class="bi bi-chat-square-text"></i> Criar modelo
                </a>
                <a href="{{ url('/whatsapp/midia') }}">
                    <i class="bi bi-image"></i> Adicionar imagem
                </a>
            </div>
        </div>
    </div>
</div>
@stop
