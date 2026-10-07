@extends('enfas.layout')
@section('title','Histórico WhatsApp')
@section('page_title','Histórico WhatsApp')
@section(
    'page_subtitle',
    $selectedAppointment
        ? 'Histórico filtrado do agendamento #'.$selectedAppointment.'.'
        : 'Envio, entrega, leitura, respostas e falhas.'
)
@section('content')
@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{$errors->first()}}</div>@endif
<div class="card mb-3"><div class="card-header"><strong>Envio manual</strong></div><form class="card-body" method="POST" action="{{url('/whatsapp/mensagens/enviar')}}">@csrf
<div class="row g-3"><div class="col-md-7"><label class="form-label">Agendamento</label><select class="form-select" name="appointment_id" required><option value="">Selecione...</option>@foreach($appointments as $a)<option value="{{$a->id}}" @selected((int)($selectedAppointment??0)===(int)$a->id)>{{$a->code}} · {{$a->patient_name}} · {{\Illuminate\Support\Carbon::parse($a->start_at)->format('d/m/Y H:i')}}</option>@endforeach</select></div><div class="col-md-5"><label class="form-label">Template aprovado</label><select class="form-select" name="template_id" required><option value="">Selecione...</option>@foreach($templates as $t)<option value="{{$t->id}}">{{$t->name}}</option>@endforeach</select></div></div>
<button class="btn btn-success mt-3"><i class="bi bi-whatsapp me-1"></i>Enviar</button>
</form></div>

@if($selectedAppointment)
<div class="alert alert-light border d-flex align-items-center justify-content-between gap-3 flex-wrap">
    <div>
        <strong>Histórico do agendamento #{{ $selectedAppointment }}</strong>
        <div class="small text-secondary">A Central abriu somente as comunicações vinculadas a este horário.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('v11.confirmations') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Central
        </a>
        <a href="{{ url('/whatsapp/mensagens') }}" class="btn btn-sm btn-outline-secondary">
            Ver todos
        </a>
    </div>
</div>
@endif

<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Data</th><th>Direção</th><th>Agendamento</th><th>Template</th><th>Destinatário</th><th>Status</th><th>Erro</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td>{{$r->created_at->format('d/m/Y H:i')}}</td><td>{{$r->direction}}</td><td>{{$r->appointment_id?'#'.$r->appointment_id:'—'}}</td><td>{{$r->template?->name?:$r->message_type}}</td><td>{{$r->recipient?:'—'}}</td><td><span class="badge text-bg-{{$r->status==='failed'?'danger':(in_array($r->status,['sent','delivered','read'])?'success':'secondary')}}">{{$r->status}}</span></td><td class="small text-danger">{{$r->error_message}}</td></tr>
@empty<tr><td colspan="7" class="text-center py-5 text-secondary">Nenhuma mensagem.</td></tr>@endforelse
</tbody></table></div><div class="card-footer">{{$rows->links()}}</div></div>
@stop
