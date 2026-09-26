@extends('enfas.layout')

@section('title','Biblioteca de mídia')
@section('page_title','Biblioteca de mídia')
@section('page_subtitle','Imagens utilizadas em modelos do WhatsApp.')

@section('content')

@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    {{ $errors->first() }}
</div>
@endif

<div class="card v9-card mb-4">
    <form
        method="POST"
        enctype="multipart/form-data"
        action="{{ route('v9.media.store') }}">
        @csrf

        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">
                        Nome
                    </label>

                    <input
                        class="form-control"
                        name="name"
                        required>
                </div>

                <div class="col-md-5">
                    <label class="form-label">
                        Imagem JPG / PNG / WebP
                    </label>

                    <input
                        class="form-control"
                        type="file"
                        name="file"
                        accept="image/*"
                        required>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-primary w-100">
                        Enviar
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="row g-3">
@forelse($rows as $row)
    <div class="col-xl-3 col-md-4 col-6">
        <div class="card v9-card h-100">
            <img
                src="{{ asset('storage/'.$row->path) }}"
                class="card-img-top"
                style="height:180px;object-fit:cover">

            <div class="card-body">
                <strong>{{ $row->name }}</strong>

                <div class="small text-secondary">
                    {{ $row->mime_type }}
                </div>

                <span class="badge text-bg-{{ $row->is_active ? 'success' : 'secondary' }}">
                    {{ $row->is_active ? 'ATIVA' : 'INATIVA' }}
                </span>
            </div>

            <div class="card-footer bg-transparent d-flex gap-2">
                <form
                    method="POST"
                    action="{{ route('v9.media.toggle',$row->id) }}">
                    @csrf
                    @method('PATCH')

                    <button class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-power"></i>
                    </button>
                </form>

                <form
                    method="POST"
                    action="{{ route('v9.media.delete',$row->id) }}"
                    onsubmit="return confirm('Excluir esta imagem?')">
                    @csrf
                    @method('DELETE')

                    <button class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
@empty
    <div class="col-12 text-center text-secondary py-5">
        Nenhuma imagem cadastrada.
    </div>
@endforelse
</div>
@stop
