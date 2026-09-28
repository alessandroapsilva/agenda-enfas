@extends('enfas.layout')

@section('title','Central WhatsApp')
@section('page_kicker','ATENDIMENTO')
@section('page_title','Central WhatsApp')
@section('page_subtitle','Inbox único para acompanhar conversas, assumir exceções e manter o contexto do paciente.')

@section('page_actions')
<a href="{{ route('v9.templates.index') }}" class="btn btn-light border">
    <i class="bi bi-chat-square-text"></i>Templates
</a>
@can('whatsapp.manage')
<a href="{{ route('enfas.v6.automations') }}" class="btn btn-primary">
    <i class="bi bi-lightning-charge"></i>Automações
</a>
@endcan
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}</div>
@endif

<div class="ea-inbox-kpis mb-3">
    @foreach([
        ['Ativas',$stats['active'],'bi-chat-dots'],
        ['Equipe',$stats['human'],'bi-headset'],
        ['Automação',$stats['bot'],'bi-robot'],
        ['Não lidas',$stats['unread'],'bi-envelope-exclamation'],
    ] as $item)
        <div class="ea-inbox-kpi">
            <span class="ea-inbox-kpi-icon"><i class="bi {{ $item[2] }}"></i></span>
            <div>
                <strong>{{ $item[1] }}</strong>
                <small>{{ $item[0] }}</small>
            </div>
        </div>
    @endforeach

    <div class="ea-inbox-connection">
        <span class="ea-status-pill {{ $integration?->last_tested_at ? 'is-online' : 'is-warning' }}">
            <span></span>{{ $integration?->last_tested_at ? 'Meta validada' : 'Meta requer validação' }}
        </span>
        @if($integration?->display_phone_number)
            <small>{{ $integration->display_phone_number }}</small>
        @endif
    </div>
</div>

<div class="ea-inbox-shell">
    <aside class="ea-inbox-list card">
        <div class="card-header">
            <div class="mb-3">
                <strong class="d-block">Conversas</strong>
                <span class="small text-secondary">Fila operacional mais recente</span>
            </div>

            <form method="GET" action="{{ route('enfas.whatsapp') }}" class="d-grid gap-2">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Paciente ou telefone">
                </div>
                <select name="mode" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas as conversas</option>
                    <option value="bot" @selected(request('mode')==='bot')>Em automação</option>
                    <option value="human" @selected(request('mode')==='human')>Com a equipe</option>
                </select>
            </form>
        </div>

        <div class="ea-conversation-list">
            @forelse($conversations as $conversation)
                <a href="{{ route('enfas.whatsapp.thread',$conversation) }}"
                   class="ea-conversation {{ $selected?->id === $conversation->id ? 'is-active' : '' }}">
                    <span class="ea-avatar ea-avatar-sm">
                        {{ strtoupper(substr($conversation->patient?->displayName() ?: $conversation->phone,0,1)) }}
                    </span>

                    <div class="ea-conversation-copy">
                        <div class="d-flex justify-content-between gap-2">
                            <strong class="text-truncate">
                                {{ $conversation->patient?->displayName() ?: $conversation->phone }}
                            </strong>
                            <time>{{ $conversation->last_message_at?->format('H:i') }}</time>
                        </div>

                        <div class="ea-conversation-meta text-truncate">
                            {{ $conversation->appointment?->service?->name ?: 'Conversa geral' }}
                            @if($conversation->appointment?->code)
                                · {{ $conversation->appointment->code }}
                            @endif
                        </div>

                        <div class="d-flex align-items-center gap-2 mt-2">
                            <span class="ea-mode-pill {{ $conversation->mode === 'human' ? 'is-human' : 'is-bot' }}">
                                <i class="bi {{ $conversation->mode === 'human' ? 'bi-headset' : 'bi-robot' }}"></i>
                                {{ $conversation->mode === 'human' ? 'Equipe' : 'Automação' }}
                            </span>
                            @if($conversation->assignedUser)
                                <small class="text-truncate">{{ $conversation->assignedUser->name }}</small>
                            @endif
                            @if($conversation->unread_count)
                                <span class="badge text-bg-danger ms-auto">{{ $conversation->unread_count }}</span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="enfas-empty">
                    <div class="enfas-empty-icon"><i class="bi bi-chat-square-text"></i></div>
                    <h5>Nenhuma conversa</h5>
                    <p>Ajuste os filtros ou aguarde uma nova interação.</p>
                </div>
            @endforelse
        </div>
    </aside>

    <main class="ea-inbox-thread card">
        @if($selected)
            <div class="card-header ea-thread-header">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <span class="ea-avatar">
                        {{ strtoupper(substr($selected->patient?->displayName() ?: $selected->phone,0,1)) }}
                    </span>
                    <div class="min-w-0">
                        <strong class="d-block text-truncate">{{ $selected->patient?->displayName() ?: $selected->phone }}</strong>
                        <span class="small text-secondary d-block text-truncate">
                            {{ $selected->phone }}
                            @if($selected->appointment?->code) · {{ $selected->appointment->code }} @endif
                        </span>
                    </div>
                </div>

                @can('whatsapp.manage')
                <div class="d-flex flex-wrap gap-2">
                    @if($selected->mode !== 'human')
                        <form method="POST" action="{{ route('enfas.whatsapp.thread.takeover',$selected) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-primary btn-sm"><i class="bi bi-headset"></i>Assumir</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('enfas.whatsapp.thread.release',$selected) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-light border btn-sm"><i class="bi bi-robot"></i>Automação</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('enfas.whatsapp.thread.close',$selected) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-light border btn-sm"><i class="bi bi-check2"></i>Encerrar</button>
                    </form>
                </div>
                @endcan
            </div>

            <div class="ea-thread-body">
                <div class="ea-message-stream" id="messageStream">
                    @forelse($messages as $message)
                        <div class="ea-message-row {{ $message->direction === 'outbound' ? 'is-outbound':'is-inbound' }}">
                            <div class="ea-message-bubble">
                                <div class="ea-message-text">{{ $message->body ?: 'Mensagem sem texto' }}</div>
                                <div class="ea-message-meta">
                                    {{ $message->created_at?->format('d/m H:i') }}
                                    @if($message->direction === 'outbound') · {{ $message->status }} @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="enfas-empty">
                            <div class="enfas-empty-icon"><i class="bi bi-chat"></i></div>
                            <h5>Sem mensagens</h5>
                            <p>A conversa ainda não possui histórico registrado.</p>
                        </div>
                    @endforelse
                </div>

                @can('whatsapp.manage')
                <div class="ea-composer">
                    <form method="POST" action="{{ route('enfas.whatsapp.thread.send',$selected) }}">
                        @csrf
                        <textarea name="message" class="form-control" rows="3" maxlength="4000"
                                  placeholder="Escreva uma mensagem administrativa para o paciente..." required></textarea>
                        <div class="ea-composer-footer">
                            <small>
                                Mensagens livres dependem da janela permitida pelo WhatsApp. Fora dela, use template aprovado.
                            </small>
                            <button class="btn btn-success">
                                <i class="bi bi-send"></i>Enviar
                            </button>
                        </div>
                    </form>
                </div>
                @else
                    <div class="ea-readonly-note">
                        <i class="bi bi-eye"></i>
                        Acesso somente para consulta desta conversa.
                    </div>
                @endcan
            </div>
        @else
            <div class="enfas-empty ea-thread-empty">
                <div class="enfas-empty-icon"><i class="bi bi-chat-heart"></i></div>
                <h5>Selecione uma conversa</h5>
                <p>O histórico e o contexto do paciente aparecem aqui.</p>
            </div>
        @endif
    </main>

    <aside class="ea-inbox-context">
        @if($selected)
            <div class="card mb-3">
                <div class="card-header">
                    <strong>Paciente</strong>
                </div>
                <div class="card-body">
                    <div class="ea-context-person">
                        <span class="ea-avatar">{{ strtoupper(substr($selected->patient?->displayName() ?: $selected->phone,0,1)) }}</span>
                        <div class="min-w-0">
                            <strong class="d-block text-truncate">{{ $selected->patient?->displayName() ?: 'Contato sem cadastro' }}</strong>
                            <small>{{ $selected->phone }}</small>
                        </div>
                    </div>

                    @if($selected->patient)
                        <dl class="ea-context-list">
                            <div><dt>RGEA</dt><dd>{{ $selected->patient->rgea_number ?: 'Não informado' }}</dd></div>
                            <div><dt>E-mail</dt><dd>{{ $selected->patient->email ?: 'Não informado' }}</dd></div>
                        </dl>

                        <a href="{{ route('patients.show',$selected->patient) }}" class="btn btn-light border w-100">
                            <i class="bi bi-person-lines-fill"></i>Abrir ficha
                        </a>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <strong>Agendamento relacionado</strong>
                </div>
                <div class="card-body">
                    @if($selected->appointment)
                        <dl class="ea-context-list">
                            <div><dt>Código</dt><dd>{{ $selected->appointment->code }}</dd></div>
                            <div><dt>Data</dt><dd>{{ $selected->appointment->start_at?->format('d/m/Y H:i') }}</dd></div>
                            <div><dt>Serviço</dt><dd>{{ $selected->appointment->service?->name ?: '—' }}</dd></div>
                            <div><dt>Profissional</dt><dd>{{ $selected->appointment->professional?->name ?: '—' }}</dd></div>
                            <div><dt>Status</dt><dd>{{ $selected->appointment->statusLabel() }}</dd></div>
                        </dl>

                        <a href="{{ route('agenda.index') }}" class="btn btn-light border w-100">
                            <i class="bi bi-calendar3"></i>Abrir agenda
                        </a>
                    @else
                        <p class="text-secondary small mb-0">Esta conversa não está vinculada a um agendamento.</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <strong>Canal oficial</strong>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="ea-wa-brand"><i class="bi bi-whatsapp"></i></span>
                    <div>
                        <strong class="d-block">{{ $integration?->verified_name ?: 'WhatsApp Business' }}</strong>
                        <small class="text-secondary">{{ $integration?->display_phone_number ?: 'Número não validado' }}</small>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="{{ route('v9.templates.index') }}" class="btn btn-light border">
                        <i class="bi bi-chat-square-text"></i>Templates
                    </a>
                    <a href="{{ route('enfas.v6.messages') }}" class="btn btn-light border">
                        <i class="bi bi-clock-history"></i>Histórico
                    </a>
                </div>
            </div>
        </div>
    </aside>
</div>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const stream = document.getElementById('messageStream');
    if (stream) stream.scrollTop = stream.scrollHeight;
});
</script>
@endpush
@stop
