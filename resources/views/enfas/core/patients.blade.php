@extends('enfas.layout')
@section('title','Pacientes')
@section('page_title','Pacientes')
@section('page_subtitle','Cadastro e localização rápida de pacientes.')
@section('content')
@if(!$ready)<div class="alert alert-warning">O cadastro de pacientes ainda não existe no banco desta instalação.</div>
@else
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Paciente</th><th>Telefone</th><th>E-mail</th><th>CPF</th><th>Status</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td><strong>{{$r->name}}</strong></td><td>{{$r->phone}}</td><td>{{$r->email??'—'}}</td><td>{{$r->cpf??'—'}}</td><td><span class="badge text-bg-{{($r->is_active??1)?'success':'secondary'}}">{{($r->is_active??1)?'Ativo':'Inativo'}}</span></td></tr>
@empty<tr><td colspan="5"><div class="ea-empty"><i class="bi bi-people"></i>Nenhum paciente cadastrado.</div></td></tr>@endforelse
</tbody></table></div>@if(method_exists($rows,'links'))<div class="card-footer">{{$rows->links()}}</div>@endif</div>
@endif
@stop
