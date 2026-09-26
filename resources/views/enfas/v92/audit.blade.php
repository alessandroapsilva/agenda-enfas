@extends('enfas.layout')
@section('title','Auditoria')
@section('page_title','Auditoria')
@section('page_subtitle','Rastreabilidade das principais ações realizadas no sistema.')

@section('content')
<div class="card v92-card">
    <div class="card-header border-0">
        <form class="d-flex gap-2" method="GET">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Pesquisar módulo, ação ou descrição...">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Data</th><th>Módulo</th><th>Ação</th><th>Descrição</th><th>Usuário</th><th>IP</th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->created_at }}</td>
                    <td><strong>{{ $r->module }}</strong></td>
                    <td><span class="badge text-bg-secondary">{{ $r->action }}</span></td>
                    <td>{{ $r->description }}</td>
                    <td>{{ $r->user_id?:'Sistema' }}</td>
                    <td>{{ $r->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-secondary py-5">Nenhum evento encontrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($rows->hasPages())
        <div class="card-footer bg-transparent">{{ $rows->links() }}</div>
    @endif
</div>
@stop
