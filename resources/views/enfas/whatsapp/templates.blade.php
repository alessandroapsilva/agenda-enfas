@extends('enfas.layout')
@section('title','Templates')
@section('page_title','Templates de mensagem')
@section('page_subtitle','Modelos cadastrados para confirmação, lembretes e avisos.')
@section('content')
@if(!$ready)<div class="alert alert-info">A Central Meta está pronta. A estrutura de templates será habilitada na etapa de envio e sincronização.</div>
@else<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Nome</th><th>Categoria</th><th>Idioma</th><th>Status</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td><strong>{{$r->name}}</strong></td><td>{{$r->category??'—'}}</td><td>{{$r->language??'—'}}</td><td>{{$r->status??'—'}}</td></tr>@empty<tr><td colspan="4"><div class="ea-empty"><i class="bi bi-chat-square-text"></i>Nenhum template cadastrado.</div></td></tr>@endforelse
</tbody></table></div></div>@endif
@stop
