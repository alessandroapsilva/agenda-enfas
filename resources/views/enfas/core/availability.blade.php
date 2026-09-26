@extends('enfas.layout')
@section('title','Disponibilidade')
@section('page_title','Disponibilidade')
@section('page_subtitle','Horários recorrentes, folgas, férias e bloqueios.')
@section('content')
<div class="row g-3">
<div class="col-md-6"><div class="ea-card border rounded-4 p-4 h-100"><div class="ea-icon mb-3"><i class="bi bi-clock"></i></div><h5>Horários semanais</h5><p class="text-secondary mb-0">@if($schedules)A estrutura de horários semanais está disponível.@elseAinda não há tabela de horários semanais nesta instalação.@endif</p></div></div>
<div class="col-md-6"><div class="ea-card border rounded-4 p-4 h-100"><div class="ea-icon mb-3"><i class="bi bi-calendar-x"></i></div><h5>Bloqueios</h5><p class="text-secondary mb-0">@if($blocks)A estrutura de bloqueios está disponível.@elseAinda não há tabela de bloqueios nesta instalação.@endif</p></div></div>
</div>
@stop
