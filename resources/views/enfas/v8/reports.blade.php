@extends('enfas.layout')
@section('title','Relatórios Premium')
@section('page_title','Relatórios Premium')
@section('page_subtitle','Indicadores operacionais dos atendimentos.')
@section('content')
<form class="card ea-v8-card mb-4" method="GET"><div class="card-body row g-3 align-items-end"><div class="col-md-4"><label class="form-label">De</label><input class="form-control" type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div><div class="col-md-4"><label class="form-label">Até</label><input class="form-control" type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div><div class="col-md-4"><button class="btn btn-primary w-100">Atualizar</button></div></div></form>
<div class="row g-3">@foreach([['Agendamentos',$metrics['appointments']],['Confirmados',$metrics['confirmed']],['Cancelados',$metrics['cancelled']],['Atendidos',$metrics['attended']],['Faltas',$metrics['no_show']]] as $m)<div class="col"><div class="ea-v8-metric"><div class="small text-secondary">{{ $m[0] }}</div><div class="fs-2 fw-semibold">{{ $m[1] }}</div></div></div>@endforeach</div>
@stop
