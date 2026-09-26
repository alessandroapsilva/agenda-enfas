@extends('enfas.layout')

@section('title','Histórico')
@section('page_title','Histórico de contatos')
@section('page_subtitle','Registro das mensagens e interações processadas pelo WhatsApp.')

@section('content')
@if(!$available)
<div class="cc-empty">
    <i class="bi bi-chat-square-dots"></i>
    <strong>Histórico ainda não disponível.</strong>
    <span>A tabela de mensagens não foi encontrada.</span>
</div>
@else
<div class="card cc-card">
    <div class="card-header border-0">
        <form method="GET" class="d-flex gap-2">
            <input
                class="form-control"
                name="q"
                value="{{ request('q') }}"
                placeholder="Pesquisar mensagem, telefone ou status">
            <button class="btn btn-outline-primary">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Direção</th>
                    <th>Telefone</th>
                    <th>Mensagem</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ isset($row->created_at) ? \Illuminate\Support\Carbon::parse($row->created_at)->format('d/m/Y H:i') : '—' }}</td>
                    <td>
                        @php($direction=$directionColumn ? ($row->{$directionColumn}??'') : '')
                        <span class="badge text-bg-{{ str_contains(strtolower($direction),'in')?'info':'light' }}">
                            {{ $direction ?: '—' }}
                        </span>
                    </td>
                    <td>{{ $phoneColumn ? ($row->{$phoneColumn}??'—') : '—' }}</td>
                    <td style="min-width:320px">
                        {{ $bodyColumn ? \Illuminate\Support\Str::limit((string)($row->{$bodyColumn}??''),180) : '—' }}
                    </td>
                    <td>
                        {{ $statusColumn ? ($row->{$statusColumn}??'—') : '—' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-secondary py-5">Nenhuma comunicação encontrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($rows->hasPages())
        <div class="card-footer bg-transparent">
            {{ $rows->links() }}
        </div>
    @endif
</div>
@endif
@stop
