@extends('enfas.layout')

@section('title','Atividades')
@section('page_kicker','OPERAÇÃO')
@section('page_title','Minhas atividades')
@section('page_subtitle','Retornos, pendências e acompanhamentos da equipe em um único lugar.')

@section('page_actions')
@can('activities.manage')
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#activityModal">
    <i class="bi bi-plus-lg"></i>Nova atividade
</button>
@endcan
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}</div>
@endif

<div class="row g-3 mb-4">
    @foreach([
        ['Abertas',$stats['open'],'bi-list-check'],
        ['Vencidas',$stats['overdue'],'bi-clock-history'],
        ['Para hoje',$stats['today'],'bi-calendar-day'],
        ['Urgentes',$stats['urgent'],'bi-exclamation-diamond'],
    ] as $item)
    <div class="col-6 col-xl-3">
        <div class="enfas-stat h-100">
            <div class="d-flex align-items-center justify-content-between">
                <span class="enfas-stat-label">{{ $item[0] }}</span>
                <span class="enfas-stat-icon"><i class="bi {{ $item[2] }}"></i></span>
            </div>
            <div class="enfas-stat-number">{{ $item[1] }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" action="{{ route('activities.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Escopo</label>
                <select name="scope" class="form-select" onchange="this.form.submit()">
                    <option value="mine" @selected($scope==='mine')>Minhas atividades</option>
                    @can('activities.manage')
                    <option value="all" @selected($scope==='all')>Toda a equipe</option>
                    @endcan
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Situação</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="open" @selected($status==='open')>Abertas</option>
                    <option value="completed" @selected($status==='completed')>Concluídas</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Prazo</label>
                <select name="when" class="form-select" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="overdue" @selected($when==='overdue')>Vencidas</option>
                    <option value="today" @selected($when==='today')>Hoje</option>
                    <option value="upcoming" @selected($when==='upcoming')>Próximas</option>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <a href="{{ route('activities.index') }}" class="btn btn-light border">Limpar filtros</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Atividade</th>
                    <th>Paciente</th>
                    <th>Responsável</th>
                    <th>Prazo</th>
                    <th>Prioridade</th>
                    <th class="text-end">Ação</th>
                </tr>
            </thead>
            <tbody>
            @forelse($tasks as $task)
                @php
                    $overdue = $task->status === 'open' && $task->due_at && strtotime($task->due_at) < time();
                    $priorityClass = match($task->priority) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'low' => 'secondary',
                        default => 'light',
                    };
                @endphp
                <tr>
                    <td>
                        <strong>{{ $task->title }}</strong>
                        @if($task->notes)
                            <div class="small text-secondary mt-1">{{ IlluminateSupportStr::limit($task->notes,120) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($task->patient_id)
                            <a href="{{ route('patients.show',$task->patient_id) }}" class="text-decoration-none">
                                {{ $task->patient_name ?: 'Paciente' }}
                            </a>
                            @if($task->patient_rgea)
                                <div class="small text-secondary">{{ $task->patient_rgea }}</div>
                            @endif
                        @else
                            <span class="text-secondary">—</span>
                        @endif
                    </td>
                    <td>{{ $task->assigned_user_name ?: 'Não atribuído' }}</td>
                    <td>
                        @if($task->due_at)
                            <span class="{{ $overdue ? 'text-danger fw-semibold':'' }}">
                                {{ date('d/m/Y H:i', strtotime($task->due_at)) }}
                            </span>
                            @if($overdue)<div class="small text-danger">Vencida</div>@endif
                        @else
                            <span class="text-secondary">Sem prazo</span>
                        @endif
                    </td>
                    <td><span class="badge text-bg-{{ $priorityClass }} {{ $priorityClass==='light' ? 'border':'' }}">{{ ucfirst($task->priority) }}</span></td>
                    <td class="text-end">
                        @if($task->status === 'open')
                            <form method="POST" action="{{ route('activities.complete',$task->id) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-light border"><i class="bi bi-check2"></i>Concluir</button>
                            </form>
                        @else
                            <span class="badge text-bg-success">Concluída</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="enfas-empty py-5">
                            <div class="enfas-empty-icon"><i class="bi bi-check2-circle"></i></div>
                            <h5>Nenhuma atividade aqui</h5>
                            <p>Quando houver retornos ou pendências, eles aparecerão nesta fila.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($tasks->hasPages())
    <div class="card-footer">{{ $tasks->links() }}</div>
    @endif
</div>

@can('activities.manage')
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('activities.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Nova atividade</h5>
                    <span class="small text-secondary">Crie um retorno ou pendência para você ou para a equipe.</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Título *</label>
                        <input name="title" class="form-control" required placeholder="Ex.: retornar confirmação do paciente">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Paciente</label>
                        <select name="patient_id" class="form-select">
                            <option value="">Sem paciente vinculado</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->name }} @if($patient->rgea_number) · {{ $patient->rgea_number }} @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Responsável</label>
                        <select name="assigned_user_id" class="form-select">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected($user->id===auth()->id())>{{ $user->name }} · {{ $user->roleLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prazo</label>
                        <input type="datetime-local" name="due_at" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Prioridade *</label>
                        <select name="priority" class="form-select" required>
                            <option value="normal">Normal</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                            <option value="low">Baixa</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea name="notes" class="form-control" rows="4"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary"><i class="bi bi-check2"></i>Criar atividade</button>
            </div>
        </form>
    </div>
</div>
@endcan
@stop
