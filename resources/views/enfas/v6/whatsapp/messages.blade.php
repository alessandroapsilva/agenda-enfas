@extends('enfas.layout')

@section('title','Histórico WhatsApp')
@section('page_kicker','Comunicação')
@section('page_title','Histórico WhatsApp')
@section(
    'page_subtitle',
    $selectedAppointment
        ? 'Comunicações vinculadas ao agendamento #'.$selectedAppointment.'.'
        : 'Envios, entregas, leituras, respostas e falhas em uma linha do tempo única.'
)

@section('page_actions')
<a
    href="{{ route('enfas.whatsapp') }}"
    class="ea-btn-secondary"
>
    <i class="bi bi-chat-dots"></i>
    Conversas
</a>

<a
    href="{{ route('v11.confirmations') }}"
    class="ea-btn-main"
>
    <i class="bi bi-patch-check"></i>
    Confirmações
</a>
@endsection

@section('content')

@php
    $statusMap = [
        'queued' => ['Na fila', 'neutral'],
        'sent' => ['Enviado', 'sent'],
        'delivered' => ['Entregue', 'delivered'],
        'read' => ['Lido', 'read'],
        'received' => ['Recebido', 'confirmed'],
        'failed' => ['Falhou', 'failed'],
    ];

    $directionMap = [
        'outbound' => ['Saída', 'bi-arrow-up-right'],
        'inbound' => ['Entrada', 'bi-arrow-down-left'],
    ];
@endphp

@if(session('success'))
    <div class="ea-flash">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i>
        {{ $errors->first() }}
    </div>
@endif

<div class="ea-message-history-layout">

    <section class="ea-saas-card">
        <div class="ea-saas-card-head">
            <div>
                <span class="ea-section-kicker">
                    Operação manual
                </span>

                <h2>Enviar template aprovado</h2>

                <p>
                    Use apenas para contatos pontuais. As automações continuam protegidas contra duplicidade.
                </p>
            </div>

            <span class="ea-status neutral">
                <i class="bi bi-shield-check"></i>
                Dedupe ativo
            </span>
        </div>

        <form
            class="ea-manual-send"
            method="POST"
            action="{{ url('/whatsapp/mensagens/enviar') }}"
        >
            @csrf

            <div class="ea-manual-send-grid">
                <div class="ea-field">
                    <label class="form-label">
                        Agendamento
                    </label>

                    <select
                        class="form-select"
                        name="appointment_id"
                        required
                    >
                        <option value="">
                            Selecione...
                        </option>

                        @foreach($appointments as $a)
                            <option
                                value="{{ $a->id }}"
                                @selected(
                                    (int) ($selectedAppointment ?? 0)
                                    === (int) $a->id
                                )
                            >
                                {{ $a->code }}
                                · {{ $a->patient_name }}
                                · {{
                                    IlluminateSupportCarbon::parse(
                                        $a->start_at
                                    )->format('d/m/Y H:i')
                                }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ea-field">
                    <label class="form-label">
                        Template aprovado
                    </label>

                    <select
                        class="form-select"
                        name="template_id"
                        required
                    >
                        <option value="">
                            Selecione...
                        </option>

                        @foreach($templates as $t)
                            <option value="{{ $t->id }}">
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button
                    class="ea-btn-main ea-manual-send-button"
                    type="submit"
                >
                    <i class="bi bi-whatsapp"></i>
                    Enviar
                </button>
            </div>
        </form>
    </section>

    @if($selectedAppointment)
        <section class="ea-context-strip">
            <div class="ea-context-strip-icon">
                <i class="bi bi-calendar-check"></i>
            </div>

            <div>
                <strong>
                    Agendamento #{{ $selectedAppointment }}
                </strong>

                <span>
                    Exibindo somente as comunicações deste horário.
                </span>
            </div>

            <div class="ea-context-strip-actions">
                <a
                    href="{{ route('v11.confirmations') }}"
                    class="ea-btn-secondary"
                >
                    <i class="bi bi-arrow-left"></i>
                    Central
                </a>

                <a
                    href="{{ url('/whatsapp/mensagens') }}"
                    class="ea-btn-ghost"
                >
                    Ver todos
                </a>
            </div>
        </section>
    @endif

    <section class="ea-saas-card">
        <div class="ea-saas-card-head">
            <div>
                <span class="ea-section-kicker">
                    Linha do tempo
                </span>

                <h2>Mensagens</h2>

                <p>
                    Histórico técnico e operacional das comunicações.
                </p>
            </div>
        </div>

        @if($rows->isEmpty())
            <div class="ea-premium-empty">
                <span class="ea-premium-empty-icon">
                    <i class="bi bi-chat-square-text"></i>
                </span>

                <strong>Nenhuma mensagem encontrada</strong>

                <p>
                    Os envios e respostas aparecerão aqui.
                </p>
            </div>
        @else
            <div class="ea-message-history">
                @foreach($rows as $r)
                    @php
                        $status =
                            $statusMap[
                                strtolower($r->status ?? '')
                            ]
                            ?? [
                                ucfirst($r->status ?? 'Status'),
                                'neutral',
                            ];

                        $direction =
                            $directionMap[
                                strtolower($r->direction ?? '')
                            ]
                            ?? ['Mensagem', 'bi-arrow-left-right'];
                    @endphp

                    <article class="ea-history-row {{
                        $r->status === 'failed'
                            ? 'is-failed'
                            : ''
                    }}">
                        <div class="ea-history-time">
                            <strong>
                                {{ $r->created_at->format('H:i') }}
                            </strong>

                            <span>
                                {{ $r->created_at->format('d/m/Y') }}
                            </span>
                        </div>

                        <div class="ea-history-direction">
                            <span class="ea-history-direction-icon">
                                <i class="bi {{ $direction[1] }}"></i>
                            </span>

                            <span>{{ $direction[0] }}</span>
                        </div>

                        <div class="ea-history-main">
                            <strong>
                                {{
                                    $r->template?->name
                                    ?: $r->message_type
                                    ?: 'Mensagem'
                                }}
                            </strong>

                            <span>
                                {{
                                    $r->recipient
                                    ?: 'Destinatário não informado'
                                }}

                                @if($r->appointment_id)
                                    · Agendamento #{{ $r->appointment_id }}
                                @endif
                            </span>

                            @if($r->error_message)
                                <small class="ea-history-error">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    {{ $r->error_message }}
                                </small>
                            @endif
                        </div>

                        <div class="ea-history-status">
                            <span class="ea-status {{ $status[1] }}">
                                {{ $status[0] }}
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <div class="ea-history-footer">
            {{ $rows->links() }}
        </div>
    </section>
</div>

@endsection
