<div class="card mt-4">
    <div class="card-header">
        <strong class="d-block">Linha do tempo clínica</strong>
        <span class="small text-secondary">Eventos longitudinais, prontuários, documentos, prescrições e anexos do paciente.</span>
    </div>
    <div class="card-body">
        @forelse($timeline as $event)
            <div class="d-flex gap-3 pb-3 mb-3 border-bottom">
                <div class="flex-shrink-0">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle border" style="width:38px;height:38px">
                        <i class="{{ $event['icon'] }}"></i>
                    </span>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if($event['link'])
                            <a href="{{ $event['link'] }}" class="fw-semibold text-decoration-none">{{ $event['title'] }}</a>
                        @else
                            <strong>{{ $event['title'] }}</strong>
                        @endif
                        @if($event['context'])<span class="badge text-bg-light border">{{ $event['context'] }}</span>@endif
                    </div>
                    @if($event['description'])<div class="mt-1">{{ $event['description'] }}</div>@endif
                    <div class="small text-secondary mt-1">
                        {{ $event['occurred_at']->format('d/m/Y H:i') }}
                        @if($event['actor']) · {{ $event['actor'] }} @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="enfas-empty py-4"><strong>Sem eventos clínicos ainda</strong><div>A timeline será construída conforme o atendimento evoluir.</div></div>
        @endforelse
    </div>
</div>
