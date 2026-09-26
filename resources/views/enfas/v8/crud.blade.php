@extends('enfas.layout')
@section('title',$title)
@section('page_title',$title)
@section('page_subtitle',$subtitle)
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="row g-4">
<div class="col-xl-4">
<div class="card ea-v8-card sticky-xl-top" style="top:1rem">
<div class="card-header border-0"><strong>{{ $editing?'Editar cadastro':'Novo cadastro' }}</strong></div>
<form method="POST" action="{{ $editing?route('v8.master.update',[$table,$editing->id]):route('v8.master.store',$table) }}">
@csrf @if($editing) @method('PATCH') @endif
<div class="card-body row g-3">
@foreach($fields as $f)
<div class="{{ $f[2]==='textarea'?'col-12':'col-md-6 col-xl-12' }}">
<label class="form-label">{{ $f[1] }}</label>
@php($v=old($f[0],$editing->{$f[0]}??''))
@if($f[2]==='textarea')
<textarea class="form-control" name="{{ $f[0] }}" rows="3">{{ $v }}</textarea>
@else
<input class="form-control" type="{{ $f[2] }}" name="{{ $f[0] }}" value="{{ $v }}" @if($f[0]==='name') required @endif @if($f[0]==='price') step="0.01" @endif>
@endif
</div>
@endforeach
</div>
<div class="card-footer bg-transparent d-flex gap-2"><button class="btn btn-primary flex-grow-1">{{ $editing?'Salvar alterações':'Cadastrar' }}</button>@if($editing)<a class="btn btn-light" href="{{ url()->current() }}">Cancelar</a>@endif</div>
</form>
</div>
</div>
<div class="col-xl-8">
<div class="card ea-v8-card"><div class="card-header border-0 d-flex justify-content-between align-items-center"><div><strong>Cadastros</strong><div class="small text-secondary">{{ $rows->count() }} registro(s)</div></div><input class="form-control form-control-sm ea-filter" style="max-width:240px" placeholder="Pesquisar..."></div>
<div class="table-responsive"><table class="table align-middle mb-0 ea-filter-table"><thead><tr><th>Nome</th><th>Contato / detalhe</th><th>Status</th><th class="text-end">Ações</th></tr></thead><tbody>
@forelse($rows as $r)
@php($active=(bool)($r->is_active??$r->active??true))
<tr><td><strong>{{ $r->name }}</strong><div class="small text-secondary">{{ $r->specialty??$r->description??'' }}</div></td><td>{{ $r->phone??$r->email??'' }} @if(isset($r->duration_minutes))<span class="badge text-bg-light">{{ $r->duration_minutes }} min</span>@endif</td><td><span class="badge text-bg-{{ $active?'success':'secondary' }}">{{ $active?'Ativo':'Inativo' }}</span></td><td class="text-end"><div class="d-inline-flex gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ url()->current().'?edit='.$r->id }}"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('v8.master.toggle',[$table,$r->id]) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning"><i class="bi bi-power"></i></button></form></div></td></tr>
@empty<tr><td colspan="4" class="text-center text-secondary py-5">Nenhum cadastro.</td></tr>@endforelse
</tbody></table></div></div>
</div></div>
@stop
