@extends('layouts.app')

@section('title', 'Serviços')
@section('page_kicker', 'CATÁLOGO DE ATENDIMENTOS')
@section('page_title', 'Serviços')
@section('page_subtitle', 'Duração, orientações, documentos, recorrência e regras de reagendamento.')

@section('page_actions')
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#serviceModal">
    <i class="bi bi-plus-lg me-1"></i>Novo serviço
</button>
@endsection

@section('content')
<div class="row g-4">
@forelse($services as $service)
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3">
                    <div class="d-flex gap-3">
                        <div class="enfas-service-color" style="background:{{ $service->color }};min-height:72px;"></div>
                        <div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <h5 class="mb-0">{{ $service->name }}</h5>
                                <span class="badge {{ $service->is_active ? 'text-bg-success':'text-bg-secondary' }}">{{ $service->is_active ? 'Ativo':'Inativo' }}</span>
                            </div>
                            <div class="text-muted small mt-1">
                                {{ $service->duration_minutes }} min · chegada {{ $service->arrival_minutes }} min antes · {{ $service->professionals_count }} profissional(is)
                            </div>
                            @if($service->code)<span class="badge text-bg-light border mt-2">{{ $service->code }}</span>@endif
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#service-edit-{{ $service->id }}"><i class="bi bi-pencil-square"></i></button>
                        <form method="POST" action="{{ route('services.status',$service) }}">@csrf @method('PATCH')<button class="btn btn-outline-secondary btn-sm">{{ $service->is_active ? 'Desativar':'Ativar' }}</button></form>
                    </div>
                </div>

                <div class="row g-2 mt-3">
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Reagendamento online</small>
                            <strong>{{ $service->allow_online_reschedule ? 'Permitido':'Bloqueado' }}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <small class="text-muted d-block mb-1">Recorrência</small>
                            <strong>{{ $service->allow_recurrence ? 'Permitida':'Bloqueada' }}</strong>
                        </div>
                    </div>
                </div>

                @if($service->description)<p class="small text-muted mt-3 mb-0">{{ $service->description }}</p>@endif

                <div class="collapse mt-4" id="service-edit-{{ $service->id }}">
                    <div class="border rounded-3 p-3 bg-body-tertiary">
                        <form method="POST" action="{{ route('services.update',$service) }}" class="row g-3">
                            @csrf @method('PATCH')
                            <div class="col-md-6"><label class="form-label">Nome</label><input name="name" class="form-control" value="{{ $service->name }}" required></div>
                            <div class="col-md-3"><label class="form-label">Código</label><input name="code" class="form-control" value="{{ $service->code }}"></div>
                            <div class="col-md-3"><label class="form-label">Cor</label><input type="color" name="color" class="form-control form-control-color" value="{{ $service->color }}"></div>
                            <div class="col-md-4"><label class="form-label">Duração</label><div class="input-group"><input type="number" name="duration_minutes" min="5" class="form-control" value="{{ $service->duration_minutes }}"><span class="input-group-text">min</span></div></div>
                            <div class="col-md-4"><label class="form-label">Chegar antes</label><div class="input-group"><input type="number" name="arrival_minutes" min="0" class="form-control" value="{{ $service->arrival_minutes }}"><span class="input-group-text">min</span></div></div>
                            <div class="col-12"><label class="form-label">Descrição</label><textarea name="description" class="form-control" rows="2">{{ $service->description }}</textarea></div>
                            <div class="col-md-6"><label class="form-label">Documentos necessários</label><textarea name="required_documents" class="form-control" rows="4">{{ $service->required_documents }}</textarea></div>
                            <div class="col-md-6"><label class="form-label">Preparo / orientações antes</label><textarea name="preparation_instructions" class="form-control" rows="4">{{ $service->preparation_instructions }}</textarea></div>
                            <div class="col-12"><label class="form-label">Orientações pós-atendimento</label><textarea name="aftercare_instructions" class="form-control" rows="3">{{ $service->aftercare_instructions }}</textarea></div>

                            <div class="col-md-6">
                                <input type="hidden" name="allow_online_reschedule" value="0">
                                <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="allow_online_reschedule" value="1" @checked($service->allow_online_reschedule)><span class="form-check-label">Permitir reagendamento pelo WhatsApp</span></label>
                            </div>
                            <div class="col-md-6">
                                <input type="hidden" name="allow_recurrence" value="0">
                                <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="allow_recurrence" value="1" @checked($service->allow_recurrence)><span class="form-check-label">Permitir agendamento recorrente</span></label>
                            </div>

                            <div class="col-12"><button class="btn btn-primary btn-sm">Salvar serviço</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12"><div class="card"><div class="enfas-empty"><div class="enfas-empty-icon"><i class="bi bi-grid"></i></div><h5>Nenhum serviço</h5></div></div></div>
@endforelse
</div>

<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" action="{{ route('services.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><div><h5 class="modal-title">Novo serviço</h5><small class="text-muted">Configure regras e orientações desde o início.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-7"><label class="form-label">Nome</label><input name="name" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Código</label><input name="code" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Cor</label><input type="color" name="color" class="form-control form-control-color" value="#2563eb"></div>
                    <div class="col-md-4"><label class="form-label">Duração</label><div class="input-group"><input type="number" name="duration_minutes" value="30" min="5" class="form-control" required><span class="input-group-text">min</span></div></div>
                    <div class="col-md-4"><label class="form-label">Antecedência</label><div class="input-group"><input type="number" name="arrival_minutes" value="15" min="0" class="form-control" required><span class="input-group-text">min</span></div></div>
                    <div class="col-12"><label class="form-label">Descrição</label><textarea name="description" rows="2" class="form-control"></textarea></div>
                    <div class="col-md-6"><label class="form-label">Documentos necessários</label><textarea name="required_documents" rows="4" class="form-control" placeholder="Documento com foto, exames anteriores..."></textarea></div>
                    <div class="col-md-6"><label class="form-label">Orientações antes</label><textarea name="preparation_instructions" rows="4" class="form-control" placeholder="Jejum, preparo, medicações..."></textarea></div>
                    <div class="col-12"><label class="form-label">Orientações após o atendimento</label><textarea name="aftercare_instructions" rows="3" class="form-control"></textarea></div>
                    <div class="col-md-6"><input type="hidden" name="allow_online_reschedule" value="0"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="allow_online_reschedule" value="1" checked><span class="form-check-label">Permitir reagendamento pelo WhatsApp</span></label></div>
                    <div class="col-md-6"><input type="hidden" name="allow_recurrence" value="0"><label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="allow_recurrence" value="1" checked><span class="form-check-label">Permitir recorrência</span></label></div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Salvar serviço</button></div>
        </form>
    </div>
</div>
@endsection
