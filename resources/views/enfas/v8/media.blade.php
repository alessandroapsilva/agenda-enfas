@extends('enfas.layout')
@section('title','Biblioteca de mídia')
@section('page_title','Biblioteca de mídia')
@section('page_subtitle','Imagens para cabeçalhos e mensagens do WhatsApp.')
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card ea-v8-card mb-4"><form method="POST" enctype="multipart/form-data" action="{{ route('v8.media.store') }}">@csrf<div class="card-body row g-3 align-items-end"><div class="col-md-5"><label class="form-label">Nome</label><input class="form-control" name="name" required></div><div class="col-md-5"><label class="form-label">Imagem</label><input class="form-control" type="file" name="file" accept="image/*" required></div><div class="col-md-2"><button class="btn btn-primary w-100">Enviar</button></div></div></form></div>
<div class="row g-3">@forelse($rows as $m)<div class="col-xl-3 col-md-4 col-6"><div class="card ea-v8-card h-100"><img src="{{ asset('storage/'.$m->path) }}" style="height:170px;object-fit:cover" class="card-img-top"><div class="card-body"><strong>{{ $m->name }}</strong><div class="small text-secondary">{{ $m->mime_type }}</div><span class="badge text-bg-{{ $m->is_active?'success':'secondary' }}">{{ $m->is_active?'Ativa':'Inativa' }}</span></div><div class="card-footer bg-transparent d-flex gap-2"><form method="POST" action="{{ route('v8.media.toggle',$m->id) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning"><i class="bi bi-power"></i></button></form><form method="POST" action="{{ route('v8.media.delete',$m->id) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></div></div></div>@empty<div class="col-12 text-center text-secondary py-5">Nenhuma mídia.</div>@endforelse</div>
@stop
