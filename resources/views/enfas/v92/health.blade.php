@extends('enfas.layout')
@section('title','Saúde do sistema')
@section('page_title','Saúde do sistema')
@section('page_subtitle','Checklist técnico de produção, fila, scheduler, backup e integrações.')

@section('content')
<div class="v92-health-summary {{ $summary['ok']?'is-ok':'is-bad' }} mb-4">
    <div>
        <span class="v92-health-dot {{ $summary['ok']?'is-ok':'is-bad' }}"></span>
        <strong>{{ $summary['ok']?'Sistema operacional':'Atenção necessária' }}</strong>
    </div>

    <div class="display-6 fw-bold">
        {{ $summary['total']-$summary['failed'] }}/{{ $summary['total'] }}
    </div>
</div>

<div class="card v92-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Status</th><th>Verificação</th><th>Detalhe</th></tr></thead>
            <tbody>
            @foreach($summary['checks'] as $check)
                <tr>
                    <td>
                        <span class="badge text-bg-{{ $check['ok']?'success':'danger' }}">
                            {{ $check['ok']?'OK':'FALHA' }}
                        </span>
                    </td>
                    <td><strong>{{ $check['label'] }}</strong></td>
                    <td class="text-secondary">{{ $check['detail']??'—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info mt-4">
    O sistema só deve ser considerado liberado para produção quando o comando
    <code>php artisan enfas:production-check</code> terminar com
    <strong>GO_PRODUCAO=true</strong>.
</div>
@stop
