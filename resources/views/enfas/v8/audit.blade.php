@extends('enfas.layout')
@section('title','Auditoria')
@section('page_title','Auditoria')
@section('page_subtitle','Rastreabilidade das alterações realizadas no sistema.')
@section('content')
<div class="card ea-v8-card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Data</th><th>Módulo</th><th>Ação</th><th>Descrição</th><th>Usuário</th><th>IP</th></tr></thead><tbody>@forelse($rows as $r)<tr><td>{{ $r->created_at }}</td><td>{{ $r->module }}</td><td><span class="badge text-bg-secondary">{{ $r->action }}</span></td><td>{{ $r->description }}</td><td>{{ $r->user_id?:'Sistema' }}</td><td>{{ $r->ip }}</td></tr>@empty<tr><td colspan="6" class="text-center text-secondary py-5">Nenhum evento.</td></tr>@endforelse</tbody></table></div></div>
@stop
