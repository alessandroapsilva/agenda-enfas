@extends('enfas.layout')
@section('title','Disponibilidade')
@section('page_title','Disponibilidade')
@section('page_subtitle','Grade semanal, férias, ausências e bloqueios por profissional.')

@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card v92-card">
            <div class="card-header border-0"><strong>Novo horário</strong></div>

            <form method="POST" action="{{ route('v92.availability.store') }}">
                @csrf

                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Profissional</label>
                        <select class="form-select" name="professional_id" required>
                            @foreach($professionals as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Unidade</label>
                        <select class="form-select" name="location_id">
                            <option value="">Todas</option>
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}">{{ $l->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Dia da semana</label>
                        <select class="form-select" name="weekday">
                            @foreach(['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $i=>$d)
                                <option value="{{ $i }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Início</label>
                        <input class="form-control" type="time" name="starts_at" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Fim</label>
                        <input class="form-control" type="time" name="ends_at" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Slot</label>
                        <input class="form-control" type="number" name="slot_minutes" value="30" min="5" max="240">
                    </div>
                </div>

                <div class="card-footer bg-transparent">
                    <button class="btn btn-primary">Adicionar disponibilidade</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card v92-card">
            <div class="card-header border-0"><strong>Férias / ausência / bloqueio</strong></div>

            <form method="POST" action="{{ route('v92.absences.store') }}">
                @csrf

                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Profissional</label>
                        <select class="form-select" name="professional_id" required>
                            @foreach($professionals as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Início</label>
                        <input class="form-control" type="datetime-local" name="starts_at" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Fim</label>
                        <input class="form-control" type="datetime-local" name="ends_at" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Motivo</label>
                        <input class="form-control" name="reason">
                    </div>
                </div>

                <div class="card-footer bg-transparent">
                    <button class="btn btn-outline-primary">Bloquear período</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card v92-card mb-4">
    <div class="card-header border-0"><strong>Grade semanal</strong></div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Profissional</th>
                    <th>Unidade</th>
                    <th>Dia</th>
                    <th>Horário</th>
                    <th>Slot</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($rules as $r)
                <tr>
                    <td><strong>{{ $r->professional_name }}</strong></td>
                    <td>{{ $r->location_name?:'Todas' }}</td>
                    <td>{{ ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][$r->weekday]??$r->weekday }}</td>
                    <td>{{ substr($r->starts_at,0,5) }}–{{ substr($r->ends_at,0,5) }}</td>
                    <td>{{ $r->slot_minutes }} min</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('v92.availability.delete',$r->id) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-secondary py-5">Nenhuma disponibilidade cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card v92-card">
    <div class="card-header border-0"><strong>Ausências e bloqueios</strong></div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Profissional</th><th>Início</th><th>Fim</th><th>Motivo</th><th></th></tr></thead>
            <tbody>
            @forelse($absences as $r)
                <tr>
                    <td>{{ $r->professional_name }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($r->starts_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($r->ends_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $r->reason }}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('v92.absences.delete',$r->id) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-secondary py-5">Nenhum bloqueio cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@stop
