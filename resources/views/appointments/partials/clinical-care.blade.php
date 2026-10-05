<div class="row g-4 mt-1">
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <strong class="d-block">Escalas e instrumentos clínicos</strong>
                <span class="small text-secondary">Registro manual do resultado informado pelo profissional.</span>
            </div>
            <div class="card-body">
                @forelse($scales as $scale)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <strong>{{ $scale->scale_name }}</strong>
                                @if($scale->scale_key)<div class="small text-secondary">{{ $scale->scale_key }}</div>@endif
                            </div>
                            @if($scale->score !== null)
                                <span class="badge text-bg-light border fs-6">{{ rtrim(rtrim((string)$scale->score,'0'),'.') }}</span>
                            @endif
                        </div>
                        @if($scale->classification)<div class="mt-2">{{ $scale->classification }}</div>@endif
                        @if($scale->notes)<div class="small text-secondary mt-2">{{ $scale->notes }}</div>@endif
                        <div class="small text-secondary mt-2">{{ $scale->recorded_at?->format('d/m/Y H:i') }}</div>
                    </div>
                @empty
                    <div class="enfas-empty py-3"><strong>Nenhuma escala registrada</strong><div>Registre instrumentos utilizados neste atendimento.</div></div>
                @endforelse

                @can('records.manage')
                <form method="POST" action="{{ route('clinical-profile.scales.store',$appointment) }}" class="border rounded p-3 mt-3">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-7"><label class="form-label">Escala / instrumento *</label><input name="scale_name" class="form-control" required placeholder="Ex.: EVA, Braden, Glasgow"></div>
                        <div class="col-md-5"><label class="form-label">Identificador</label><input name="scale_key" class="form-control" placeholder="Opcional"></div>
                        <div class="col-md-4"><label class="form-label">Escore</label><input type="number" step="0.01" name="score" class="form-control"></div>
                        <div class="col-md-8"><label class="form-label">Classificação / resultado</label><input name="classification" class="form-control" maxlength="160"></div>
                        <div class="col-12"><label class="form-label">Observações</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                    <div class="small text-secondary mt-2">O sistema registra o resultado; a interpretação clínica permanece sob responsabilidade do profissional.</div>
                    <button class="btn btn-light border mt-3"><i class="bi bi-plus-lg me-1"></i>Registrar escala</button>
                </form>
                @endcan
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <strong class="d-block">Plano terapêutico / assistencial</strong>
                <span class="small text-secondary">Metas, ações, responsável e acompanhamento.</span>
            </div>
            <div class="card-body">
                @forelse($carePlans as $plan)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <strong>{{ $plan->goal }}</strong>
                                <div class="small text-secondary mt-1">{{ $plan->actions }}</div>
                            </div>
                            <span class="badge {{ match($plan->status) {'completed'=>'text-bg-success','in_progress'=>'text-bg-primary','cancelled'=>'text-bg-secondary',default=>'text-bg-warning'} }}">
                                {{ match($plan->status) {'completed'=>'Concluído','in_progress'=>'Em andamento','cancelled'=>'Cancelado',default=>'Planejado'} }}
                            </span>
                        </div>
                        <div class="small text-secondary mt-2">
                            @if($plan->responsibleProfessional)Responsável: {{ $plan->responsibleProfessional->name }} @endif
                            @if($plan->target_date)· Prazo: {{ $plan->target_date->format('d/m/Y') }}@endif
                        </div>

                        @can('records.manage')
                        <form method="POST" action="{{ route('clinical-profile.care-plans.status',[$appointment,$plan]) }}" class="d-flex gap-2 mt-3">
                            @csrf @method('PATCH')
                            <select name="status" class="form-select form-select-sm">
                                <option value="planned" @selected($plan->status==='planned')>Planejado</option>
                                <option value="in_progress" @selected($plan->status==='in_progress')>Em andamento</option>
                                <option value="completed" @selected($plan->status==='completed')>Concluído</option>
                                <option value="cancelled" @selected($plan->status==='cancelled')>Cancelado</option>
                            </select>
                            <button class="btn btn-sm btn-light border">Atualizar</button>
                        </form>
                        @endcan
                    </div>
                @empty
                    <div class="enfas-empty py-3"><strong>Plano ainda não estruturado</strong><div>Adicione metas e ações para acompanhamento deste atendimento.</div></div>
                @endforelse

                @can('records.manage')
                <form method="POST" action="{{ route('clinical-profile.care-plans.store',$appointment) }}" class="border rounded p-3 mt-3">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Meta *</label><input name="goal" class="form-control" required maxlength="500"></div>
                        <div class="col-12"><label class="form-label">Ações planejadas *</label><textarea name="actions" class="form-control" rows="3" required></textarea></div>
                        <div class="col-md-6"><label class="form-label">Prazo</label><input type="date" name="target_date" class="form-control"></div>
                        <input type="hidden" name="responsible_professional_id" value="{{ $appointment->professional_id }}">
                    </div>
                    <button class="btn btn-light border mt-3"><i class="bi bi-plus-lg me-1"></i>Adicionar meta</button>
                </form>
                @endcan
            </div>
        </div>
    </div>
</div>
