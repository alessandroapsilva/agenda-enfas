@extends('layouts.app')

@section('title', 'Profissionais')
@section('page_kicker', 'EQUIPE E DISPONIBILIDADE')
@section('page_title', 'Profissionais')
@section('page_subtitle', 'Equipe, serviços habilitados, jornada, pausas e bloqueios de agenda.')

@section('page_actions')
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#professionalModal">
    <i class="bi bi-person-plus me-1"></i>Novo profissional
</button>
@endsection

@section('content')

<div class="row g-4">
@forelse($professionals as $professional)
    @php
        $dayNames = [0=>'Dom',1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb'];
        $availabilityByDay = $professional->availabilities->keyBy('day_of_week');
        $professionalBlocks = $blocks->get($professional->id, collect());
    @endphp
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div class="d-flex gap-3">
                        <div class="enfas-prof-avatar" style="--prof-color: {{ $professional->color }}">
                            {{ strtoupper(substr($professional->name,0,1)) }}
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <h5 class="mb-0">{{ $professional->name }}</h5>
                                <span class="badge {{ $professional->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $professional->is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                                @if($professional->whatsapp_notifications_enabled)
                                    <span class="badge text-bg-light border"><i class="bi bi-whatsapp me-1 text-success"></i>Notificações</span>
                                @endif
                            </div>
                            <div class="text-muted small mt-1">
                                {{ $professional->specialty ?: 'Profissional' }}
                                @if($professional->phone) · {{ $professional->phone }} @endif
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                @forelse($professional->services as $service)
                                    <span class="badge text-bg-light border">{{ $service->name }}</span>
                                @empty
                                    <span class="text-muted small">Nenhum serviço vinculado</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#edit-prof-{{ $professional->id }}">
                            <i class="bi bi-pencil-square me-1"></i>Cadastro
                        </button>
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#availability-prof-{{ $professional->id }}">
                            <i class="bi bi-clock-history me-1"></i>Disponibilidade
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#block-prof-{{ $professional->id }}">
                            <i class="bi bi-calendar-x me-1"></i>Bloqueios
                        </button>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3">
                            <small class="text-muted d-block">Jornada padrão</small>
                            <strong>{{ substr($professional->work_start,0,5) }} – {{ substr($professional->work_end,0,5) }}</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3">
                            <small class="text-muted d-block">Intervalo entre slots</small>
                            <strong>{{ $professional->slot_interval }} min</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3">
                            <small class="text-muted d-block">Bloqueios futuros</small>
                            <strong>{{ $professionalBlocks->count() }}</strong>
                        </div>
                    </div>
                </div>

                <div class="collapse mt-4" id="edit-prof-{{ $professional->id }}">
                    <div class="border rounded-3 p-3 bg-body-tertiary">
                        <form method="POST" action="{{ route('professionals.update',$professional) }}" class="row g-3">
                            @csrf @method('PATCH')
                            <div class="col-md-5"><label class="form-label">Nome</label><input name="name" class="form-control" value="{{ $professional->name }}" required></div>
                            <div class="col-md-3"><label class="form-label">Especialidade</label><input name="specialty" class="form-control" value="{{ $professional->specialty }}"></div>
                            <div class="col-md-2"><label class="form-label">Início</label><input type="time" name="work_start" class="form-control" value="{{ substr($professional->work_start,0,5) }}" required></div>
                            <div class="col-md-2"><label class="form-label">Fim</label><input type="time" name="work_end" class="form-control" value="{{ substr($professional->work_end,0,5) }}" required></div>
                            <div class="col-md-4"><label class="form-label">WhatsApp</label><input name="phone" class="form-control" value="{{ $professional->phone }}"></div>
                            <div class="col-md-4"><label class="form-label">E-mail</label><input type="email" name="email" class="form-control" value="{{ $professional->email }}"></div>
                            <div class="col-md-2"><label class="form-label">Slot</label><input type="number" min="5" name="slot_interval" class="form-control" value="{{ $professional->slot_interval }}"></div>
                            <div class="col-md-2"><label class="form-label">Cor</label><input type="color" name="color" class="form-control form-control-color" value="{{ $professional->color }}"></div>

                            <div class="col-12">
                                <label class="form-label">Dias ativos</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach($dayNames as $day => $label)
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox" name="active_days[]" value="{{ $day }}" @checked(in_array($day,$professional->active_days ?? [],true))>
                                            <span class="form-check-label">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Serviços habilitados</label>
                                <div class="row g-2">
                                    @foreach($services as $service)
                                        <div class="col-md-4">
                                            <label class="form-check">
                                                <input class="form-check-input" type="checkbox" name="services[]" value="{{ $service->id }}" @checked($professional->services->contains('id',$service->id))>
                                                <span class="form-check-label">{{ $service->name }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-12">
                                <input type="hidden" name="whatsapp_notifications_enabled" value="0">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="whatsapp_notifications_enabled" value="1" @checked($professional->whatsapp_notifications_enabled)>
                                    <span class="form-check-label">Receber notificações de confirmação, cancelamento e reagendamento</span>
                                </label>
                            </div>

                            <div class="col-12"><button class="btn btn-primary btn-sm">Salvar profissional</button></div>
                        </form>
                    </div>
                </div>

                <div class="collapse mt-4" id="availability-prof-{{ $professional->id }}">
                    <div class="border rounded-3 p-3">
                        <h6>Disponibilidade semanal</h6>
                        <p class="text-muted small">Defina jornada e pausa por dia. O motor de reagendamento usa estes horários.</p>
                        <form method="POST" action="{{ route('professionals.availability',$professional) }}">
                            @csrf
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead><tr><th>Dia</th><th>Atende</th><th>Início</th><th>Fim</th><th>Pausa início</th><th>Pausa fim</th></tr></thead>
                                    <tbody>
                                    @foreach($dayNames as $day => $label)
                                        @php $row=$availabilityByDay->get($day); @endphp
                                        <tr>
                                            <td><strong>{{ $label }}</strong></td>
                                            <td>
                                                <input type="hidden" name="availability[{{ $day }}][enabled]" value="0">
                                                <input class="form-check-input" type="checkbox" name="availability[{{ $day }}][enabled]" value="1" @checked($row || in_array($day,$professional->active_days ?? [],true))>
                                            </td>
                                            <td><input type="time" class="form-control form-control-sm" name="availability[{{ $day }}][start_time]" value="{{ $row ? substr($row->start_time,0,5) : substr($professional->work_start,0,5) }}"></td>
                                            <td><input type="time" class="form-control form-control-sm" name="availability[{{ $day }}][end_time]" value="{{ $row ? substr($row->end_time,0,5) : substr($professional->work_end,0,5) }}"></td>
                                            <td><input type="time" class="form-control form-control-sm" name="availability[{{ $day }}][break_start]" value="{{ $row?->break_start ? substr($row->break_start,0,5) : '' }}"></td>
                                            <td><input type="time" class="form-control form-control-sm" name="availability[{{ $day }}][break_end]" value="{{ $row?->break_end ? substr($row->break_end,0,5) : '' }}"></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button class="btn btn-primary btn-sm">Salvar disponibilidade</button>
                        </form>
                    </div>
                </div>

                <div class="collapse mt-4" id="block-prof-{{ $professional->id }}">
                    <div class="border rounded-3 p-3">
                        <div class="row g-4">
                            <div class="col-lg-5">
                                <h6>Novo bloqueio</h6>
                                <form method="POST" action="{{ route('professionals.blocks.store',$professional) }}" class="row g-2">
                                    @csrf
                                    <div class="col-12">
                                        <select name="type" class="form-select">
                                            <option value="block">Bloqueio</option>
                                            <option value="absence">Ausência</option>
                                            <option value="vacation">Férias</option>
                                            <option value="meeting">Reunião</option>
                                        </select>
                                    </div>
                                    <div class="col-12"><input name="title" class="form-control" placeholder="Título"></div>
                                    <div class="col-md-6"><input type="datetime-local" name="starts_at" class="form-control" required></div>
                                    <div class="col-md-6"><input type="datetime-local" name="ends_at" class="form-control" required></div>
                                    <div class="col-12"><textarea name="reason" class="form-control" rows="2" placeholder="Motivo / observação"></textarea></div>
                                    <div class="col-12">
                                        <input type="hidden" name="is_all_day" value="0">
                                        <label class="form-check"><input class="form-check-input" type="checkbox" name="is_all_day" value="1"><span class="form-check-label">Dia inteiro</span></label>
                                    </div>
                                    <div class="col-12"><button class="btn btn-outline-primary btn-sm">Adicionar bloqueio</button></div>
                                </form>
                            </div>
                            <div class="col-lg-7">
                                <h6>Próximos bloqueios</h6>
                                @forelse($professionalBlocks as $block)
                                    <div class="border rounded-3 p-3 mb-2 d-flex justify-content-between gap-3">
                                        <div>
                                            <strong>{{ $block->title ?: ucfirst($block->type) }}</strong>
                                            <div class="text-muted small">{{ \Carbon\Carbon::parse($block->starts_at)->format('d/m/Y H:i') }} → {{ \Carbon\Carbon::parse($block->ends_at)->format('d/m/Y H:i') }}</div>
                                            @if($block->reason)<div class="small mt-1">{{ $block->reason }}</div>@endif
                                        </div>
                                        <form method="POST" action="{{ route('professionals.blocks.destroy',[$professional,$block->id]) }}">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                @empty
                                    <div class="text-muted small">Nenhum bloqueio futuro.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12"><div class="card"><div class="enfas-empty"><div class="enfas-empty-icon"><i class="bi bi-person-badge"></i></div><h5>Nenhum profissional</h5></div></div></div>
@endforelse
</div>

<div class="modal fade" id="professionalModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" action="{{ route('professionals.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><div><h5 class="modal-title">Novo profissional</h5><small class="text-muted">Cadastre a equipe e vincule os serviços.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-7"><label class="form-label">Nome</label><input name="name" class="form-control" required></div>
                    <div class="col-md-5"><label class="form-label">Especialidade</label><input name="specialty" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">WhatsApp</label><input name="phone" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">E-mail</label><input type="email" name="email" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Início</label><input type="time" name="work_start" value="08:00" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Fim</label><input type="time" name="work_end" value="18:00" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Intervalo</label><input type="number" name="slot_interval" value="30" min="5" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Cor</label><input type="color" name="color" value="#2563eb" class="form-control form-control-color"></div>

                    <div class="col-12">
                        <label class="form-label">Dias ativos</label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach([0=>'Dom',1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb'] as $day=>$label)
                                <label class="form-check"><input class="form-check-input" type="checkbox" name="active_days[]" value="{{ $day }}" @checked(in_array($day,[1,2,3,4,5]))><span class="form-check-label">{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Serviços habilitados</label>
                        <div class="row g-2">
                            @foreach($services as $service)
                                <div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" name="services[]" value="{{ $service->id }}"><span class="form-check-label">{{ $service->name }}</span></label></div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12">
                        <input type="hidden" name="whatsapp_notifications_enabled" value="0">
                        <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="whatsapp_notifications_enabled" value="1" checked><span class="form-check-label">Receber notificações pelo WhatsApp</span></label>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Salvar profissional</button></div>
        </form>
    </div>
</div>

@endsection
