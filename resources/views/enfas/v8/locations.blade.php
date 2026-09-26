@extends('enfas.layout')
@section('title','Unidades')
@section('page_title','Unidades e locais')
@section('page_subtitle','Endereços, salas e pontos de atendimento.')
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row g-4"><div class="col-xl-4"><div class="card ea-v8-card"><div class="card-header border-0"><strong>Nova unidade</strong></div><form method="POST" action="{{ route('v8.locations.store') }}">@csrf<div class="card-body row g-3">
@foreach([['name','Nome'],['phone','Telefone'],['email','E-mail'],['address','Endereço'],['city','Cidade'],['state','UF'],['postal_code','CEP']] as $f)<div class="{{ in_array($f[0],['name','address'])?'col-12':'col-6' }}"><label class="form-label">{{ $f[1] }}</label><input class="form-control" name="{{ $f[0] }}" @if($f[0]==='name') required @endif></div>@endforeach
<div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="notes"></textarea></div></div><div class="card-footer bg-transparent"><button class="btn btn-primary w-100">Cadastrar unidade</button></div></form></div></div>
<div class="col-xl-8"><div class="card ea-v8-card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Unidade</th><th>Endereço</th><th>Contato</th><th>Status</th><th></th></tr></thead><tbody>@foreach($rows as $r)<tr><td><strong>{{ $r->name }}</strong></td><td>{{ $r->address }} {{ $r->city }} {{ $r->state }}</td><td>{{ $r->phone }}<br><small>{{ $r->email }}</small></td><td><span class="badge text-bg-{{ $r->is_active?'success':'secondary' }}">{{ $r->is_active?'Ativa':'Inativa' }}</span></td><td><form method="POST" action="{{ route('v8.locations.toggle',$r->id) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning"><i class="bi bi-power"></i></button></form></td></tr>@endforeach</tbody></table></div></div></div></div>
@stop
