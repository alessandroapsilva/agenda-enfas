@extends('enfas.layout')
@section('title','Automações')
@section('page_title','Automações de comunicação')
@section('page_subtitle','Regras ligadas ao ciclo do agendamento.')
@section('content')
@if(!$ready)<div class="alert alert-info">A estrutura de automações ainda não está disponível nesta base.</div>
@else<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Nome</th><th>Gatilho</th><th>Status</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td><strong>{{$r->name}}</strong></td><td>{{$r->trigger_event??'—'}}</td><td>{{($r->is_active??0)?'Ativa':'Inativa'}}</td></tr>@empty<tr><td colspan="3"><div class="ea-empty"><i class="bi bi-lightning-charge"></i>Nenhuma automação cadastrada.</div></td></tr>@endforelse
</tbody></table></div></div>@endif
@stop
