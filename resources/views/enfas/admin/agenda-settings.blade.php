@extends('enfas.layout')
@section('title','Preferências da agenda')
@section('page_title','Preferências da agenda')
@section('page_subtitle','Comportamento padrão utilizado pelos atendentes.')
@section('content')
<div class="card"><form method="POST" action="{{url('/configuracoes/agenda')}}" class="card-body">@csrf
<div class="row g-3"><div class="col-md-4"><label class="form-label">Intervalo padrão</label><div class="input-group"><input class="form-control" type="number" name="default_slot" value="{{$slot}}" min="5" max="240"><span class="input-group-text">min</span></div></div><div class="col-md-4"><label class="form-label">Início do dia</label><input class="form-control" type="time" name="day_start" value="{{$start}}"></div><div class="col-md-4"><label class="form-label">Fim do dia</label><input class="form-control" type="time" name="day_end" value="{{$end}}"></div></div>
<div class="row g-3 mt-2"><div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="confirmation_enabled" value="1" id="c1" {{$confirmation?'checked':''}}><label class="form-check-label" for="c1">Solicitar confirmação</label></div></div><div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="reminders_enabled" value="1" id="c2" {{$reminders?'checked':''}}><label class="form-check-label" for="c2">Lembretes automáticos</label></div></div></div>
<button class="btn btn-primary mt-4">Salvar preferências</button>
</form></div>
@stop
