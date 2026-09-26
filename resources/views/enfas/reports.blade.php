@extends('enfas.layout')
@section('title','Relatórios')
@section('page_title','Relatórios')
@section('page_subtitle','Indicadores operacionais consolidados.')
@section('content')
<div class="row g-3">@foreach($cards as $label=>$value)<div class="col-xl-3 col-md-6"><div class="ea-stat"><strong>{{$value}}</strong><span>{{$label}}</span></div></div>@endforeach</div>
@stop
