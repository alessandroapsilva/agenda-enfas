<div class="row g-4 mt-1">
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <strong class="d-block">Evoluções clínicas</strong>
                <span class="small text-secondary">Entradas independentes, assinadas e imutáveis após o registro.</span>
            </div>
            <div class="card-body">
                @forelse($evolutions as $evolution)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <strong>{{ $evolution->format === 'soap' ? 'Evolução SOAP' : 'Evolução narrativa' }}</strong>
                                <div class="small text-secondary">
                                    {{ $evolution->signed_at?->format('d/m/Y H:i') }}
                                    @if($evolution->author) · {{ $evolution->author->name }} @endif
                                </div>
                            </div>
                            <span class="badge text-bg-success"><i class="bi bi-shield-check me-1"></i>Assinada</span>
                        </div>

                        @if($evolution->format === 'soap')
                            @foreach([
                                'S · Subjetivo'=>$evolution->subjective,
                                'O · Objetivo'=>$evolution->objective,
                                'A · Avaliação'=>$evolution->assessment,
                                'P · Plano'=>$evolution->plan,
                            ] as $label=>$value)
                                @if($value)
                                    <div class="mt-3"><strong class="small d-block">{{ $label }}</strong><div>{{ $value }}</div></div>
                                @endif
                            @endforeach
                        @else
                            <div class="mt-3">{{ $evolution->body }}</div>
                        @endif

                        <div class="small text-secondary mt-3">Integridade SHA-256</div>
                        <code class="small text-break">{{ $evolution->integrity_hash }}</code>
                    </div>
                @empty
                    <div class="enfas-empty py-3"><strong>Nenhuma evolução assinada</strong><div>Use SOAP ou narrativa para registrar novas evoluções.</div></div>
                @endforelse

                @can('records.manage')
                <form method="POST" action="{{ route('clinical-workflow.evolutions.store',$appointment) }}" class="border rounded p-3 mt-3">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Formato *</label>
                        <select name="format" class="form-select">
                            <option value="soap">SOAP estruturado</option>
                            <option value="narrative">Narrativa</option>
                        </select>
                        <div class="form-text">Para SOAP, preencha ao menos um dos quatro campos. Para narrativa, use o campo de texto livre.</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">S · Subjetivo</label><textarea name="subjective" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">O · Objetivo</label><textarea name="objective" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">A · Avaliação</label><textarea name="assessment" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">P · Plano</label><textarea name="plan" class="form-control" rows="3"></textarea></div>
                        <div class="col-12">
                            <label class="form-label">Narrativa</label>
                            <textarea name="body" class="form-control" rows="5" placeholder="Use este campo quando selecionar o formato narrativo."></textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" onclick="return confirm('Registrar esta evolução? Após salvar ela ficará imutável.');">
                        <i class="bi bi-shield-check me-1"></i>Assinar evolução
                    </button>
                </form>
                @endcan
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <strong class="d-block">Protocolos e checklists</strong>
                <span class="small text-secondary">Execução rastreável de protocolos configurados para o atendimento.</span>
            </div>
            <div class="card-body">
                @can('records.manage')
                @if($protocolTemplates->isNotEmpty())
                <form method="POST" action="{{ route('clinical-workflow.protocols.start',$appointment) }}" class="d-flex gap-2 mb-4">
                    @csrf
                    <select name="template_id" class="form-select" required>
                        <option value="">Selecione um protocolo</option>
                        @foreach($protocolTemplates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-light border flex-shrink-0"><i class="bi bi-play-fill me-1"></i>Iniciar</button>
                </form>
                @else
                    <div class="alert alert-light border small">Nenhum template de protocolo ativo. Execute o seeder de protocolos na implantação.</div>
                @endif
                @endcan

                @forelse($protocolRuns as $run)
                    @php($responsesByItem = $run->responses->keyBy('template_item_id'))
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between gap-3 mb-3">
                            <div>
                                <strong>{{ $run->template->name }}</strong>
                                <div class="small text-secondary">
                                    Iniciado em {{ $run->started_at?->format('d/m/Y H:i') }}
                                    @if($run->completed_at) · concluído em {{ $run->completed_at->format('d/m/Y H:i') }} @endif
                                </div>
                            </div>
                            @if($run->status === 'completed')
                                <span class="badge text-bg-success">Concluído</span>
                            @else
                                <span class="badge text-bg-warning">Em andamento</span>
                            @endif
                        </div>

                        @if($run->status !== 'completed')
                        @can('records.manage')
                        <form method="POST" action="{{ route('clinical-workflow.protocols.update',[$appointment,$run]) }}">
                            @csrf @method('PATCH')
                            @foreach($run->template->items as $item)
                                @php($response = $responsesByItem->get($item->id))
                                <div class="border-top pt-3 mt-3">
                                    <div class="d-flex justify-content-between gap-2">
                                        <label class="form-label fw-semibold mb-1">
                                            {{ $item->label }}
                                            @if($item->is_required)<span class="text-danger">*</span>@endif
                                        </label>
                                    </div>
                                    @if($item->description)<div class="small text-secondary mb-2">{{ $item->description }}</div>@endif
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <select name="responses[{{ $item->id }}][value]" class="form-select form-select-sm">
                                                <option value="">Selecione</option>
                                                <option value="done" @selected($response?->value==='done')>Realizado / conferido</option>
                                                <option value="not_done" @selected($response?->value==='not_done')>Não realizado</option>
                                                <option value="not_applicable" @selected($response?->value==='not_applicable')>Não se aplica</option>
                                            </select>
                                        </div>
                                        <div class="col-md-7">
                                            <input name="responses[{{ $item->id }}][notes]" value="{{ $response?->notes }}" class="form-control form-control-sm" placeholder="Observação opcional">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            <button class="btn btn-light border mt-3"><i class="bi bi-save me-1"></i>Salvar checklist</button>
                        </form>

                        <form method="POST" action="{{ route('clinical-workflow.protocols.complete',[$appointment,$run]) }}" class="mt-2" onsubmit="return confirm('Concluir este protocolo? Após a conclusão as respostas ficarão bloqueadas.');">
                            @csrf
                            <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Concluir protocolo</button>
                        </form>
                        @endcan
                        @else
                            @foreach($run->template->items as $item)
                                @php($response = $responsesByItem->get($item->id))
                                <div class="border-top pt-2 mt-2">
                                    <strong class="small">{{ $item->label }}</strong>
                                    <div>
                                        {{ match($response?->value) {'done'=>'Realizado / conferido','not_done'=>'Não realizado','not_applicable'=>'Não se aplica',default=>'Sem resposta'} }}
                                        @if($response?->notes) · {{ $response->notes }} @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @empty
                    <div class="enfas-empty py-3"><strong>Nenhum protocolo executado</strong><div>Inicie um template para criar um checklist rastreável.</div></div>
                @endforelse
            </div>
        </div>
    </div>
</div>
