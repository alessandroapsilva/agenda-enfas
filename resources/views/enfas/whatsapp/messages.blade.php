@extends('enfas.layout')
@section('title','Histórico de mensagens')
@section('page_title','Histórico de mensagens')
@section('page_subtitle','Envio, entrega, leitura, respostas e falhas.')
@section('content')
@if(!$ready)<div class="alert alert-info">O histórico começará a ser preenchido quando o envio oficial estiver habilitado.</div>
@else<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Data</th><th>Direção</th><th>Destinatário</th><th>Status</th><th>Tipo</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td>{{$r->created_at??'—'}}</td><td>{{$r->direction??'—'}}</td><td>{{$r->recipient??'—'}}</td><td>{{$r->status??'—'}}</td><td>{{$r->message_type??'—'}}</td></tr>@empty<tr><td colspan="5"><div class="ea-empty"><i class="bi bi-clock-history"></i>Nenhuma mensagem registrada.</div></td></tr>@endforelse
</tbody></table></div></div>@endif
@stop
