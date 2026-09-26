@extends('enfas.layout')

@section('title','Central')
@section('page_title','Central ENFAS')
@section('page_subtitle','Atendimento, cadastros, WhatsApp e gestão em um único lugar.')

@section('content')
<div class="ea-hero mb-4">
    <div class="ea-hero__content">
        <div>
            <div class="ea-eyebrow">
                OPERAÇÃO
            </div>

            <h2 class="ea-hero__title">
                Boa operação começa com uma visão simples.
            </h2>

            <p class="ea-hero__text">
                Acesse os módulos principais sem procurar em vários menus.
            </p>
        </div>

        <div class="ea-hero__actions">
            <a
                href="{{ url('/agenda') }}"
                class="btn btn-primary btn-lg">
                <i class="bi bi-plus-lg me-2"></i>
                Novo atendimento
            </a>

            <button
                type="button"
                class="btn btn-light btn-lg"
                data-enfas-command>
                <i class="bi bi-search me-2"></i>
                Buscar módulo
                <kbd class="ms-2">Ctrl K</kbd>
            </button>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['Atendimentos hoje',$metrics['appointments_today'],'bi-calendar2-check'],
        ['Pacientes ativos',$metrics['patients'],'bi-people'],
        ['Profissionais',$metrics['professionals'],'bi-person-badge'],
        ['Serviços',$metrics['services'],'bi-grid'],
        ['Templates aprovados',$metrics['templates_approved'],'bi-whatsapp'],
        ['Mensagens hoje',$metrics['messages_today'],'bi-send'],
    ] as $metric)
        <div class="col-6 col-md-4 col-xl-2">
            <div class="ea-stat">
                <div class="ea-stat__icon">
                    <i class="bi {{ $metric[2] }}"></i>
                </div>

                <div class="ea-stat__value">
                    {{ $metric[1] }}
                </div>

                <div class="ea-stat__label">
                    {{ $metric[0] }}
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-9">
        @foreach($modules as $group)
            <section class="mb-4">
                <div class="ea-section-title">
                    <div>
                        <span class="ea-section-title__dot"></span>
                        {{ $group['group'] }}
                    </div>
                </div>

                <div class="row g-3">
                    @foreach($group['items'] as $item)
                        <div class="col-12 col-sm-6 col-lg-4">
                            <a
                                href="{{ url($item['url']) }}"
                                class="ea-module">
                                <div class="ea-module__icon">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                </div>

                                <div class="ea-module__body">
                                    <strong>
                                        {{ $item['title'] }}
                                    </strong>

                                    <span>
                                        {{ $item['subtitle'] }}
                                    </span>
                                </div>

                                <i class="bi bi-arrow-up-right ea-module__arrow"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <div class="col-xl-3">
        <div class="ea-side-panel mb-3">
            <div class="ea-side-panel__title">
                <i class="bi bi-whatsapp"></i>
                WhatsApp
            </div>

            <div class="ea-side-row">
                <span>Integração</span>
                <span class="badge text-bg-{{ $meta['configured'] ? 'success' : 'secondary' }}">
                    {{ $meta['configured'] ? 'Conectada' : 'Pendente' }}
                </span>
            </div>

            <div class="ea-side-row">
                <span>Aprovados</span>
                <strong>{{ $metrics['templates_approved'] }}</strong>
            </div>

            <div class="ea-side-row">
                <span>Em análise</span>
                <strong>{{ $metrics['templates_pending'] }}</strong>
            </div>

            <a
                href="{{ url('/whatsapp') }}"
                class="btn btn-outline-primary w-100 mt-3">
                Abrir central
            </a>
        </div>

        <div class="ea-side-panel">
            <div class="ea-side-panel__title">
                <i class="bi bi-lightning-charge"></i>
                Ações rápidas
            </div>

            <a
                href="{{ url('/profissionais') }}"
                class="ea-quick">
                <i class="bi bi-person-plus"></i>
                Novo profissional
            </a>

            <a
                href="{{ url('/pacientes') }}"
                class="ea-quick">
                <i class="bi bi-person-vcard"></i>
                Novo paciente
            </a>

            <a
                href="{{ url('/whatsapp/templates?novo=1') }}"
                class="ea-quick">
                <i class="bi bi-chat-square-text"></i>
                Novo modelo
            </a>

            <a
                href="{{ url('/whatsapp/midia') }}"
                class="ea-quick">
                <i class="bi bi-image"></i>
                Enviar imagem
            </a>
        </div>
    </div>
</div>
@stop
