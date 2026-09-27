@extends('enfas.layout')
@section('title','Agendamentos')
@section('page_title','Agendamentos')
@section('page_subtitle','Histórico geral e códigos de agendamento.')
@section('content')
@if(!$ready)<div class="alert alert-warning">A tabela de agendamentos ainda não existe nesta instalação.</div>
@else
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Código</th><th>Início</th><th>Status</th><th>Confirmação</th><th>Origem</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td><strong>{{$r->code??('#'.$r->id)}}</strong></td><td>{{isset($r->start_at)?\Illuminate\Support\Carbon::parse($r->start_at)->format('d/m/Y H:i'):'—'}}</td><td>{{$r->status??'—'}}</td><td>{{$r->confirmation_status??'—'}}</td><td>{{$r->source??'—'}}</td></tr>
@empty<tr><td colspan="5"><div class="ea-empty"><i class="bi bi-calendar2-check"></i>Nenhum agendamento cadastrado.</div></td></tr>@endforelse
</tbody></table></div></div>
@endif
@stop
