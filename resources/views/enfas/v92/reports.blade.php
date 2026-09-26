@extends('enfas.layout')
@section('title','Relatórios')
@section('page_title','Relatórios gerenciais')
@section('page_subtitle','Indicadores operacionais para acompanhar a agenda e a produtividade.')

@section('content')
<form class="card v92-card mb-4" method="GET">
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">De</label>
            <input class="form-control" type="date" name="from" value="{{ $from->format('Y-m-d') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Até</label>
            <input class="form-control" type="date" name="to" value="{{ $to->format('Y-m-d') }}">
        </div>
        <div class="col-md-4">
            <button class="btn btn-primary w-100">Atualizar período</button>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
@foreach([
    ['Agendamentos',$metrics['appointments'],'bi-calendar3'],
    ['Confirmados',$metrics['confirmed'],'bi-check2-circle'],
    ['Cancelados',$metrics['cancelled'],'bi-x-circle'],
    ['Atendidos',$metrics['attended'],'bi-person-check'],
    ['Faltas',$metrics['no_show'],'bi-person-x'],
] as $m)
    <div class="col">
        <div class="v92-stat">
            <i class="bi {{ $m[2] }}"></i>
            <strong>{{ $m[1] }}</strong>
            <span>{{ $m[0] }}</span>
        </div>
    </div>
@endforeach
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card v92-card">
            <div class="card-header border-0"><strong>Profissionais mais agendados</strong></div>
            <div class="card-body">
                @forelse($topProfessionals as $r)
                    <div class="v92-ranking">
                        <span>{{ $r->name }}</span>
                        <strong>{{ $r->total }}</strong>
                    </div>
                @empty
                    <div class="text-secondary">Sem dados no período.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card v92-card">
            <div class="card-header border-0"><strong>Serviços mais agendados</strong></div>
            <div class="card-body">
                @forelse($topServices as $r)
                    <div class="v92-ranking">
                        <span>{{ $r->name }}</span>
                        <strong>{{ $r->total }}</strong>
                    </div>
                @empty
                    <div class="text-secondary">Sem dados no período.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@stop
