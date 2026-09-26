@extends('enfas.layout')

@section('title','Aparência')
@section('page_title','Aparência e identidade')
@section(
  'page_subtitle',
  'Personalize o ENFAS Agenda sem alterar código.'
)

@section('content')

@if(session('success'))
<div class="alert alert-success">
  <i class="bi bi-check-circle me-2"></i>
  {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
  <i class="bi bi-exclamation-triangle me-2"></i>
  {{ $errors->first() }}
</div>
@endif

<form
  method="POST"
  action="{{ url('/configuracoes/aparencia') }}"
  enctype="multipart/form-data">
@csrf

<div class="row g-4">

  <div class="col-xl-4">
    <div class="card h-100">
      <div class="card-header">
        <strong>Pré-visualização</strong>
      </div>

      <div class="card-body">
        <div class="ea-brand-preview">
          <div class="text-center">
            <img src="{{ $logoUrl }}" alt="Logo atual">
            <h5 class="mt-3 mb-1">{{ $systemName }}</h5>
            <div class="small text-secondary">
              {{ $organizationName ?: 'Identidade do sistema' }}
            </div>
          </div>
        </div>

        <div class="small text-secondary mt-3">
          O logo salvo será usado na sidebar e nas telas do sistema.
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-8">
    <div class="card">
      <div class="card-header">
        <strong>Identidade visual</strong>
      </div>

      <div class="card-body">
        <div class="row g-3">

          <div class="col-md-6">
            <label class="form-label">Nome do sistema</label>
            <input
              class="form-control"
              name="system_name"
              value="{{ old('system_name',$systemName) }}"
              required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Organização</label>
            <input
              class="form-control"
              name="organization_name"
              value="{{ old('organization_name',$organizationName) }}">
          </div>

          <div class="col-md-3">
            <label class="form-label">Cor principal</label>
            <input
              class="form-control form-control-color w-100"
              type="color"
              name="primary_color"
              value="{{ old('primary_color',$primaryColor) }}"
              required>
          </div>

          <div class="col-md-9">
            <label class="form-label">Logo</label>
            <input
              class="form-control"
              type="file"
              name="logo"
              accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp">
            <small class="text-secondary">
              PNG, JPG ou WebP. Até 4 MB.
            </small>
          </div>

          <div class="col-md-9 offset-md-3">
            <label class="form-label">Favicon</label>
            <input
              class="form-control"
              type="file"
              name="favicon"
              accept=".png,.jpg,.jpeg,.webp,.ico,image/*">
          </div>

          @if($logoPath)
          <div class="col-md-6 offset-md-3">
            <label class="form-check">
              <input
                class="form-check-input"
                type="checkbox"
                name="remove_logo"
                value="1">
              Voltar ao logo padrão
            </label>
          </div>
          @endif

          @if($faviconPath)
          <div class="col-md-6">
            <label class="form-check">
              <input
                class="form-check-input"
                type="checkbox"
                name="remove_favicon"
                value="1">
              Voltar ao favicon padrão
            </label>
          </div>
          @endif

        </div>
      </div>

      <div class="card-footer text-end">
        <button class="btn btn-primary px-4">
          <i class="bi bi-check2 me-1"></i>
          Salvar aparência
        </button>
      </div>
    </div>
  </div>

</div>
</form>
@stop
