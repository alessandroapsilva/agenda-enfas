@extends('enfas.layout')

@section('title','Central de Atendimento')
@section('page_kicker','WHATSAPP BUSINESS')
@section('page_title','Central de Atendimento')
@section('page_subtitle','Conversas, robô, equipe e agendamentos em uma única operação.')

@section('content')
@if(session('success'))
<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}</div>
@endif

<div class="row g-3 mb-4">
    @foreach([
        ['Conversas ativas',$stats['active'],'bi-chat-dots'],
        ['Com atendente',$stats['human'],'bi-headset'],
        ['Com robô',$stats['bot'],'bi-robot'],
        ['Não lidas',$stats['unread'],'bi-envelope-exclamation'],
    ] as $item)
    <div class="col-6 col-xl-3">
        <div class="enfas-stat">
            <div class="enfas-stat-top">
                <span class="enfas-stat-label">{{ $item[0] }}</span>
                <span class="enfas-stat-icon"><i class="bi {{ $item[2] }}"></i></span>
            </div>
            <div class="enfas-stat-number">{{ $item[1] }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card" style="min-height:720px;">
            <div class="card-header">
                <form method="GET" action="{{ route('enfas.whatsapp') }}" class="row g-2">
                    <div class="col-8">
                        <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Paciente ou telefone">
                    </div>
                    <div class="col-4">
                        <select name="mode" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="bot" @selected(request('mode')==='bot')>Robô</option>
                            <option value="human" @selected(request('mode')==='human')>Humano</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="list-group list-group-flush">
                @forelse($conversations as $conversation)
                    <a href="{{ route('enfas.whatsapp.thread',$conversation) }}"
                       class="list-group-item list-group-item-action py-3 {{ $selected?->id === $conversation->id ? 'active' : '' }}">
                        <div class="d-flex justify-content-between gap-3">
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">
                                    {{ $conversation->patient?->displayName() ?: $conversation->phone }}
                                </div>
                                <div class="small {{ $selected?->id === $conversation->id ? 'text-white-50' : 'text-muted' }}">
                                    {{ $conversation->appointment?->service?->name ?: 'Conversa geral' }}
                                    @if($conversation->appointment?->code)
                                        · {{ $conversation->appointment->code }}
                                    @endif
                                </div>
                            </div>

                            <div class="text-end">
                                @if($conversation->unread_count)
                                    <span class="badge rounded-pill text-bg-danger">{{ $conversation->unread_count }}</span>
                                @endif
                                <div class="small mt-1 {{ $selected?->id === $conversation->id ? 'text-white-50' : 'text-muted' }}">
                                    {{ $conversation->last_message_at?->format('H:i') }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 d-flex gap-1">
                            <span class="badge {{ $conversation->mode === 'human' ? 'text-bg-primary':'text-bg-light border' }}">
                                {{ $conversation->mode === 'human' ? 'Humano':'Robô' }}
                            </span>
                            @if($conversation->assignedUser)
                                <span class="badge text-bg-light border">{{ $conversation->assignedUser->name }}</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-chat-square-text fs-2 d-block mb-2"></i>
                        Nenhuma conversa encontrada.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        @if($selected)
            <div class="card" style="min-height:720px;">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="mb-1">{{ $selected->patient?->displayName() ?: $selected->phone }}</h5>
                        <div class="small text-muted">
                            {{ $selected->phone }}
                            @if($selected->appointment)
                                · {{ $selected->appointment->service?->name }}
                                · {{ $selected->appointment->professional?->name }}
                                · {{ $selected->appointment->code }}
                            @endif
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        @if($selected->mode !== 'human')
                            <form method="POST" action="{{ route('enfas.whatsapp.thread.takeover',$selected) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-primary btn-sm"><i class="bi bi-headset me-1"></i>Assumir atendimento</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('enfas.whatsapp.thread.release',$selected) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-outline-primary btn-sm"><i class="bi bi-robot me-1"></i>Devolver ao robô</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('enfas.whatsapp.thread.close',$selected) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-outline-secondary btn-sm">Encerrar</button>
                        </form>
                    </div>
                </div>

                <div class="card-body d-flex flex-column">
                    @if($selected->appointment)
                        <div class="border rounded-3 p-3 mb-3 bg-body-tertiary">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Agendamento</small>
                                    <strong>{{ $selected->appointment->code }}</strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Data e hora</small>
                                    <strong>{{ $selected->appointment->start_at?->format('d/m/Y H:i') }}</strong>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Status</small>
                                    <strong>{{ $selected->appointment->statusLabel() }}</strong>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex-grow-1 overflow-auto pe-1" style="max-height:460px;">
                        @forelse($messages as $message)
                            <div class="d-flex mb-3 {{ $message->direction === 'outbound' ? 'justify-content-end':'justify-content-start' }}">
                                <div class="rounded-4 px-3 py-2 {{ $message->direction === 'outbound' ? 'bg-primary text-white':'bg-body-tertiary border' }}" style="max-width:78%;">
                                    <div class="small" style="white-space:pre-wrap;">{{ $message->body ?: 'Mensagem sem texto' }}</div>
                                    <div class="mt-1 opacity-75" style="font-size:.68rem;">
                                        {{ $message->created_at?->format('d/m H:i') }}
                                        @if($message->direction === 'outbound') · {{ $message->status }} @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-5">Nenhuma mensagem registrada.</div>
                        @endforelse
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <form method="POST" action="{{ route('enfas.whatsapp.thread.send',$selected) }}">
                            @csrf
                            <div class="input-group">
                                <textarea name="message" class="form-control" rows="2" maxlength="4000" placeholder="Digite a mensagem para o paciente..." required></textarea>
                                <button class="btn btn-success px-4">
                                    <i class="bi bi-send me-1"></i>Enviar
                                </button>
                            </div>
                            <div class="form-text">
                                Ao enviar manualmente, a conversa fica em atendimento humano.
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <div class="card" style="min-height:720px;">
                <div class="enfas-empty">
                    <div class="enfas-empty-icon"><i class="bi bi-headset"></i></div>
                    <h5>Central pronta</h5>
                    <p>Selecione uma conversa à esquerda para acompanhar o paciente, o agendamento e assumir o atendimento.</p>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <strong>Integração Meta</strong>
            <div class="small text-muted">
                {{ $integration?->last_tested_at ? 'Conexão validada' : 'Requer validação' }}
                @if($integration?->display_phone_number) · {{ $integration->display_phone_number }} @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('enfas.v6.templates') }}" class="btn btn-outline-secondary btn-sm">Modelos</a>
            <a href="{{ route('enfas.v6.automations') }}" class="btn btn-outline-secondary btn-sm">Automações</a>
            <a href="{{ route('enfas.v6.messages') }}" class="btn btn-outline-secondary btn-sm">Histórico</a>
        </div>
    </div>
</div>
@stop
