@extends('layouts.app')

@section('title','Retirada de medicamentos')
@section('page_kicker','LOGÍSTICA DO PACIENTE')
@section('page_title','Retirada de medicamentos')
@section('page_subtitle','Acompanhe horários, separação, liberação e retirada dos medicamentos.')

@section('page_actions')
<a href="{{ route('agenda.index') }}" class="btn btn-primary">
    <i class="bi bi-calendar-plus me-1"></i>Novo agendamento
</a>
@endsection

@section('content')

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3 mb-4">
    @foreach([
        ['Agendadas',$metrics['scheduled'],'bi-calendar2-check'],
        ['Em separação',$metrics['preparing'],'bi-box-seam'],
        ['Prontas',$metrics['ready'],'bi-bag-check'],
        ['Retiradas hoje',$metrics['collected_today'],'bi-check2-circle'],
    ] as $metric)
        <div class="col-6 col-xl-3">
            <div class="enfas-stat h-100">
                <div class="enfas-stat-top">
                    <span class="enfas-stat-label">{{ $metric[0] }}</span>
                    <span class="enfas-stat-icon"><i class="bi {{ $metric[2] }}"></i></span>
                </div>
                <div class="enfas-stat-number">{{ $metric[1] }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <strong class="d-block">Fila de retirada</strong>
            <span class="small text-secondary">Prioridade para retiradas ainda em andamento.</span>
        </div>

        <form method="GET" class="d-flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Paciente, RGEA ou medicamento">
            <select name="status" class="form-select">
                <option value="">Todos os status</option>
                <option value="scheduled" @selected($status==='scheduled')>Agendada</option>
                <option value="preparing" @selected($status==='preparing')>Em separação</option>
                <option value="ready" @selected($status==='ready')>Pronta</option>
                <option value="collected" @selected($status==='collected')>Retirada</option>
                <option value="not_collected" @selected($status==='not_collected')>Não retirada</option>
                <option value="cancelled" @selected($status==='cancelled')>Cancelada</option>
            </select>
            <button class="btn btn-outline-primary">Filtrar</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Horário</th>
                    <th>Paciente</th>
                    <th>RGEA</th>
                    <th>Medicamento</th>
                    <th>Quantidade</th>
                    <th>Status</th>
                    <th class="text-end">Operação</th>
                </tr>
            </thead>
            <tbody>
            @forelse($pickups as $pickup)
                @php
                    $labels = [
                        'scheduled' => ['Agendada','secondary'],
                        'preparing' => ['Em separação','warning'],
                        'ready' => ['Pronta','primary'],
                        'collected' => ['Retirada','success'],
                        'not_collected' => ['Não retirada','danger'],
                        'cancelled' => ['Cancelada','secondary'],
                    ];
                    [$pickupLabel,$pickupColor] = $labels[$pickup->pickup_status] ?? ['Sem status','secondary'];
                @endphp
                <tr>
                    <td>
                        <strong>{{ $pickup->start_at->format('d/m/Y H:i') }}</strong>
                        <div class="small text-secondary">{{ $pickup->code }}</div>
                    </td>
                    <td>
                        <a href="{{ route('patients.show',$pickup->patient) }}" class="fw-semibold text-decoration-none">
                            {{ $pickup->patient?->displayName() }}
                        </a>
                        <div class="small text-secondary">{{ $pickup->patient?->phone }}</div>
                    </td>
                    <td>
                        @if($pickup->patient?->rgea_number)
                            <span class="badge text-bg-light border">{{ $pickup->patient->rgea_number }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <strong>{{ $pickup->medication_name }}</strong>
                        @if($pickup->medication_notes)
                            <div class="small text-secondary">{{ IlluminateSupportStr::limit($pickup->medication_notes,70) }}</div>
                        @endif
                    </td>
                    <td>{{ $pickup->medication_quantity }}</td>
                    <td><span class="badge text-bg-{{ $pickupColor }}">{{ $pickupLabel }}</span></td>
                    <td class="text-end" style="min-width:290px">
                        <form method="POST" action="{{ route('medication-pickups.status',$pickup) }}" class="d-flex flex-column flex-lg-row justify-content-end gap-2">
                            @csrf
                            @method('PATCH')

                            @php
                                $nextStatuses = match($pickup->pickup_status) {
                                    'scheduled' => ['scheduled'=>'Agendada','preparing'=>'Em separação','cancelled'=>'Cancelada'],
                                    'preparing' => ['preparing'=>'Em separação','ready'=>'Pronta','cancelled'=>'Cancelada'],
                                    'ready' => ['ready'=>'Pronta','collected'=>'Retirada','not_collected'=>'Não retirada','cancelled'=>'Cancelada'],
                                    'collected' => ['collected'=>'Retirada'],
                                    'not_collected' => ['not_collected'=>'Não retirada'],
                                    'cancelled' => ['cancelled'=>'Cancelada'],
                                    default => ['scheduled'=>'Agendada'],
                                };
                            @endphp
                            <select name="pickup_status" class="form-select form-select-sm">
                                @foreach($nextStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected($pickup->pickup_status===$value)>{{ $label }}</option>
                                @endforeach
                            </select>

                            <input name="pickup_collected_by" class="form-control form-control-sm" value="{{ $pickup->pickup_collected_by }}" placeholder="Quem retirou">
                            <input name="pickup_collector_document" class="form-control form-control-sm" value="{{ $pickup->pickup_collector_document }}" placeholder="Documento">

                            <button class="btn btn-sm btn-primary">
                                Salvar
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="enfas-empty">
                            <div class="enfas-empty-icon"><i class="bi bi-capsule"></i></div>
                            <h5>Nenhuma retirada encontrada</h5>
                            <p>Crie um agendamento do tipo retirada de medicamento.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        {{ $pickups->links() }}
    </div>
</div>
@stop
