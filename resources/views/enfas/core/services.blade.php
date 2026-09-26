@extends('enfas.layout')
@section('title','Serviços')
@section('page_title','Serviços')
@section('page_subtitle','Tipos de atendimento e duração padrão.')
@section('content')
@if(!$ready)<div class="alert alert-warning">O cadastro de serviços ainda não existe no banco desta instalação.</div>
@else
<div class="row g-3">@forelse($rows as $r)<div class="col-xl-4 col-md-6"><div class="ea-card border rounded-4 p-3 h-100"><div class="d-flex gap-3 align-items-center"><div class="ea-icon"><i class="bi bi-calendar2-plus"></i></div><div><strong>{{$r->name}}</strong><div class="small text-secondary">{{$r->duration_minutes??30}} minutos · {{$r->code??'sem código'}}</div></div></div>@if(!empty($r->description))<p class="small text-secondary mt-3 mb-0">{{$r->description}}</p>@endif</div></div>
@empty<div class="col-12"><div class="ea-empty"><i class="bi bi-grid"></i>Nenhum serviço cadastrado.</div></div>@endforelse</div>
@endif
@stop
