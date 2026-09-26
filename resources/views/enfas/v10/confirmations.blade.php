@extends('enfas.layout')

@section('title','Confirmações')
@section('page_title','Confirmações')
@section('page_subtitle','Fila operacional de confirmações, cancelamentos e presença.')

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3 mb-4">
@foreach([
    ['pending','Aguardando',$metrics['pending'],'warning'],
    ['confirmed','Confirmados',$metrics['confirmed'],'success'],
    ['cancelled','Cancelados',$metrics['cancelled'],'danger'],
    ['attended','Atendidos',$metrics['attended'],'primary'],
    ['no_show','Faltas',$metrics['no_show'],'secondary'],
] as $m)
<div class="col">
    <a href="{{ url('/confirmacoes?status='.$m[0]) }}" class="cc-filter-card {{ $filter===$m[0]?'is-active':'' }}">
        <span>{{ $m[1] }}</span>
        <strong>{{ $m[2] }}</strong>
        <i class="cc-filter-dot text-bg-{{ $m[3] }}"></i>
    </a>
</div>
@endforeach
</div>

<div class="card cc-card">
    <div class="card-header border-0">
        <form method="GET" class="row g-2">
            <input type="hidden" name="status" value="{{ $filter }}">

            <div class="col-md-7">
                <input
                    class="form-control"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Paciente, telefone, profissional ou serviço">
            </div>

            <div class="col-md-3">
                <input
                    class="form-control"
                    type="date"
                    name="date"
                    value="{{ request('date') }}">
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Agendamento</th>
                    <th>Data/Hora</th>
                    <th>Contato</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>

            <tbody>
            @forelse($rows as $row)
                @php
                    $status = filled($row->no_show_at??null)
                        ? ['Falta','secondary']
                        : (filled($row->attended_at??null)
                            ? ['Atendido','primary']
                            : (filled($row->cancelled_at??null)
                                ? ['Cancelado','danger']
                                : (filled($row->confirmed_at??null)
                                    ? ['Confirmado','success']
                                    : ['Aguardando','warning'])));
                    $dateValue=$dateColumn ? ($row->{$dateColumn}??null) : null;
                @endphp

                <tr>
                    <td>
                        <strong>{{ $row->patient_name }}</strong>
                        <div class="small text-secondary">#{{ $row->id }}</div>
                    </td>

                    <td>
                        {{ $row->service_name }}
                        <div class="small text-secondary">
                            {{ $row->professional_name }}
                            @if(isset($row->location_name) && $row->location_name)
                                · {{ $row->location_name }}
                            @endif
                        </div>
                    </td>

                    <td>
                        {{ $dateValue ? \Illuminate\Support\Carbon::parse($dateValue)->format('d/m/Y H:i') : '—' }}
                    </td>

                    <td>
                        <div>{{ $row->patient_phone }}</div>
                        <a href="{{ url('/whatsapp/mensagens') }}" class="small text-decoration-none">
                            ver mensagens
                        </a>
                    </td>

                    <td>
                        <span class="badge text-bg-{{ $status[1] }}">{{ $status[0] }}</span>
                    </td>

                    <td class="text-end">
                        <div class="d-inline-flex flex-wrap gap-1">
                            <a
                                href="{{ url('/agendamentos/'.$row->id) }}"
                                class="btn btn-sm btn-light"
                                title="Abrir agendamento">
                                <i class="bi bi-eye"></i>
                            </a>

                            @if(!filled($row->confirmed_at??null))
                            <form method="POST" action="{{ route('v10.confirmations.mark',$row->id) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="confirmed">
                                <button class="btn btn-sm btn-outline-success" title="Confirmar">
                                    <i class="bi bi-check2"></i>
                                </button>
                            </form>
                            @endif

                            @if(!filled($row->cancelled_at??null))
                            <form method="POST" action="{{ route('v10.confirmations.mark',$row->id) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="cancelled">
                                <button class="btn btn-sm btn-outline-danger" title="Cancelar">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('v10.confirmations.mark',$row->id) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="action" value="pending">
                                <button class="btn btn-sm btn-outline-secondary" title="Voltar para aguardando">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-secondary py-5">
                        Nenhum agendamento neste filtro.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($rows->hasPages())
        <div class="card-footer bg-transparent">
            {{ $rows->links() }}
        </div>
    @endif
</div>
@stop
