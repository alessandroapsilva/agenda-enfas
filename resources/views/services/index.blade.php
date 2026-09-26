@extends('layouts.app')

@section('title', 'Serviços')

@section('content_header')

<div class="d-flex justify-content-between align-items-end">

    <div>
        <h3 class="enfas-page-title">
            Serviços
        </h3>

        <p class="enfas-page-subtitle">
            Tipos e duração dos agendamentos.
        </p>
    </div>

    <button
        class="btn btn-primary"
        data-bs-toggle="modal"
        data-bs-target="#serviceModal">

        <i class="bi bi-plus-lg me-1"></i>
        Novo serviço

    </button>

</div>

@stop


@section('content')

<div class="row g-3">

    @forelse($services as $service)

        <div class="col-xl-4 col-md-6">

            <div class="card h-100">

                <div class="card-body">

                    <div class="d-flex gap-3">

                        <div
                            class="enfas-service-color"
                            style="background: {{ $service->color }}">
                        </div>

                        <div class="flex-grow-1">

                            <h6 class="fw-semibold mb-1">
                                {{ $service->name }}
                            </h6>

                            <div class="small text-secondary">
                                {{ $service->duration_minutes }} minutos
                            </div>

                            @if($service->code)

                                <span class="badge text-bg-light mt-2">
                                    {{ $service->code }}
                                </span>

                            @endif

                        </div>

                    </div>

                    @if($service->description)

                        <p class="small text-secondary mt-3 mb-0">
                            {{ $service->description }}
                        </p>

                    @endif

                </div>

            </div>

        </div>

    @empty

        <div class="col-12">

            <div class="card">

                <div class="enfas-empty">

                    <div class="enfas-empty-icon">
                        <i class="bi bi-grid"></i>
                    </div>

                    <h5>Nenhum serviço</h5>

                    <p>
                        Cadastre o primeiro tipo de atendimento.
                    </p>

                </div>

            </div>

        </div>

    @endforelse

</div>


<div class="modal fade"
     id="serviceModal"
     tabindex="-1">

    <div class="modal-dialog">

        <form
            method="POST"
            action="{{ route('services.store') }}"
            class="modal-content">

            @csrf

            <div class="modal-header">

                <h5 class="modal-title">
                    Novo serviço
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <div class="mb-3">

                    <label class="form-label">
                        Nome
                    </label>

                    <input
                        name="name"
                        class="form-control"
                        required>

                </div>

                <div class="row g-3">

                    <div class="col-6">

                        <label class="form-label">
                            Código
                        </label>

                        <input
                            name="code"
                            class="form-control"
                            placeholder="CONSULTA">

                    </div>

                    <div class="col-6">

                        <label class="form-label">
                            Duração
                        </label>

                        <div class="input-group">

                            <input
                                type="number"
                                name="duration_minutes"
                                value="30"
                                min="5"
                                class="form-control"
                                required>

                            <span class="input-group-text">
                                min
                            </span>

                        </div>

                    </div>

                    <div class="col-12">

                        <label class="form-label">
                            Cor
                        </label>

                        <input
                            type="color"
                            name="color"
                            value="#2563eb"
                            class="form-control form-control-color">

                    </div>

                </div>

                <div class="mt-3">

                    <label class="form-label">
                        Descrição
                    </label>

                    <textarea
                        name="description"
                        rows="3"
                        class="form-control">
                    </textarea>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    class="btn btn-primary">
                    Salvar
                </button>

            </div>

        </form>

    </div>

</div>

@stop
