@extends('enfas.layout')
@section('title','Profissionais')
@section('page_title','Profissionais')
@section('page_subtitle','Equipe habilitada para receber agendamentos.')
@section('content')
@if(!$ready)<div class="alert alert-warning">O cadastro de profissionais ainda não existe no banco desta instalação.</div>
@else
<div class="row g-3">@forelse($rows as $r)<div class="col-xl-4 col-md-6"><div class="ea-card border rounded-4 p-3 h-100"><div class="d-flex gap-3 align-items-center"><div class="ea-icon"><i class="bi bi-person"></i></div><div><strong>{{$r->name}}</strong><div class="small text-secondary">{{$r->specialty??'Profissional'}}</div></div></div><hr><div class="small text-secondary">{{substr($r->work_start??'08:00',0,5)}}–{{substr($r->work_end??'18:00',0,5)}} · intervalo {{$r->slot_interval??30}} min</div></div></div>
@empty<div class="col-12"><div class="ea-empty"><i class="bi bi-person-badge"></i>Nenhum profissional cadastrado.</div></div>@endforelse</div>
@endif
@stop
