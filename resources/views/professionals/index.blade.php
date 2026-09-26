@extends('layouts.app')

@section('title', 'Profissionais')

@section('content_header')

<div class="d-flex justify-content-between align-items-end">

    <div>

        <h3 class="enfas-page-title">
            Profissionais
        </h3>

        <p class="enfas-page-subtitle">
            Equipe e disponibilidade da agenda.
        </p>

    </div>

    <button
        class="btn btn-primary"
        data-bs-toggle="modal"
        data-bs-target="#professionalModal">

        <i class="bi bi-person-plus me-1"></i>
        Novo profissional

    </button>

</div>

@stop


@section('content')

<div class="row g-3">

    @forelse($professionals as $professional)

        <div class="col-xl-4 col-md-6">

            <div class="card h-100">

                <div class="card-body">

                    <div class="d-flex align-items-center gap-3">

                        <div
                            class="enfas-prof-avatar"
                            style="--prof-color: {{ $professional->color }}">

                            {{ strtoupper(substr($professional->name, 0, 1)) }}

                        </div>

                        <div>

                            <h6 class="mb-1 fw-semibold">
                                {{ $professional->name }}
                            </h6>

                            <div class="small text-secondary">
                                {{ $professional->specialty ?: 'Profissional' }}
                            </div>

                        </div>

                    </div>

                    <hr>

                    <div class="small text-secondary mb-2">

                        <i class="bi bi-clock me-1"></i>

                        {{ substr($professional->work_start, 0, 5) }}
                        –
                        {{ substr($professional->work_end, 0, 5) }}

                    </div>

                    <div class="d-flex flex-wrap gap-1">

                        @forelse($professional->services as $service)

                            <span class="badge text-bg-light">
                                {{ $service->name }}
                            </span>

                        @empty

                            <span class="small text-secondary">
                                Todos os serviços
                            </span>

                        @endforelse

                    </div>

                </div>

            </div>

        </div>

    @empty

        <div class="col-12">

            <div class="card">

                <div class="enfas-empty">

                    <div class="enfas-empty-icon">
                        <i class="bi bi-person-badge"></i>
                    </div>

                    <h5>Nenhum profissional</h5>

                </div>

            </div>

        </div>

    @endforelse

</div>


<div class="modal fade"
     id="professionalModal"
     tabindex="-1">

    <div class="modal-dialog modal-lg">

        <form
            method="POST"
            action="{{ route('professionals.store') }}"
            class="modal-content">

            @csrf

            <div class="modal-header">

                <h5 class="modal-title">
                    Novo profissional
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <div class="row g-3">

                    <div class="col-md-7">

                        <label class="form-label">
                            Nome
                        </label>

                        <input
                            name="name"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-5">

                        <label class="form-label">
                            Especialidade
                        </label>

                        <input
                            name="specialty"
                            class="form-control">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Telefone
                        </label>

                        <input
                            name="phone"
                            class="form-control">

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            E-mail
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control">

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Início
                        </label>

                        <input
                            type="time"
                            name="work_start"
                            value="08:00"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Fim
                        </label>

                        <input
                            type="time"
                            name="work_end"
                            value="18:00"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Intervalo
                        </label>

                        <input
                            type="number"
                            name="slot_interval"
                            value="30"
                            min="5"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Cor
                        </label>

                        <input
                            type="color"
                            name="color"
                            value="#2563eb"
                            class="form-control form-control-color">

                    </div>

                    <div class="col-12">

                        <label class="form-label">
                            Serviços habilitados
                        </label>

                        <select
                            name="services[]"
                            class="form-select"
                            multiple
                            size="5">

                            @foreach($services as $service)

                                <option value="{{ $service->id }}">
                                    {{ $service->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button class="btn btn-primary">
                    Salvar profissional
                </button>

            </div>

        </form>

    </div>

</div>

@stop
