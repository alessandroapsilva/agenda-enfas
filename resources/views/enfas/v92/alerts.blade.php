@extends('enfas.layout')
@section('title','Alertas')
@section('page_title','Central de alertas')
@section('page_subtitle','Pendências técnicas e operacionais que precisam de atenção.')

@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="row g-3 mb-4">
@forelse($alerts as $a)
    <div class="col-lg-6">
        <div class="v92-alert v92-alert-{{ $a['severity'] }}">
            <div>
                <strong>{{ $a['title'] }}</strong>
                <p>{{ $a['message'] }}</p>
            </div>
            <a href="{{ url($a['url']) }}" class="btn btn-sm btn-light">Abrir</a>
        </div>
    </div>
@empty
    <div class="col-12">
        <div class="v92-empty-state">
            <i class="bi bi-check2-circle"></i>
            <strong>Nenhuma pendência automática.</strong>
            <span>Os principais indicadores estão normais.</span>
        </div>
    </div>
@endforelse
</div>

@if($manual->count())
<div class="card v92-card">
    <div class="card-header border-0"><strong>Alertas registrados</strong></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Severidade</th><th>Origem</th><th>Alerta</th><th>Data</th><th></th></tr></thead>
            <tbody>
            @foreach($manual as $r)
                <tr>
                    <td><span class="badge text-bg-{{ $r->severity==='danger'?'danger':($r->severity==='warning'?'warning':'info') }}">{{ strtoupper($r->severity) }}</span></td>
                    <td>{{ $r->source }}</td>
                    <td><strong>{{ $r->title }}</strong><div class="small text-secondary">{{ $r->message }}</div></td>
                    <td>{{ $r->created_at }}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('v92.alerts.resolve',$r->id) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-success">Resolver</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@stop
