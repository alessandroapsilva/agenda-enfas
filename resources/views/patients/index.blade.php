@extends('layouts.app')

@section('title', 'Pacientes')

@section('content_header')

<div class="d-flex justify-content-between align-items-end gap-3">

    <div>

        <h3 class="enfas-page-title">
            Pacientes
        </h3>

        <p class="enfas-page-subtitle">
            Cadastro e localização rápida.
        </p>

    </div>

    <button
        class="btn btn-primary"
        data-bs-toggle="modal"
        data-bs-target="#patientModal">

        <i class="bi bi-person-plus me-1"></i>

        Novo paciente

    </button>

</div>

@stop


@section('content')

<div class="card">

    <div class="card-header">

        <form
            method="GET"
            class="d-flex gap-2">

            <input
                type="search"
                name="q"
                value="{{ $search }}"
                class="form-control"
                placeholder="Nome, telefone ou CPF"
            >

            <button class="btn btn-outline-secondary">
                <i class="bi bi-search"></i>
            </button>

        </form>

    </div>

    <div class="table-responsive">

        <table class="table align-middle mb-0">

            <thead>
            <tr>
                <th>Paciente</th>
                <th>Telefone</th>
                <th>E-mail</th>
                <th>CPF</th>
                <th class="text-end">Agendamentos</th>
            </tr>
            </thead>

            <tbody>

            @forelse($patients as $patient)

                <tr>

                    <td>
                        <strong>{{ $patient->name }}</strong>
                    </td>

                    <td>{{ $patient->phone }}</td>

                    <td>
                        {{ $patient->email ?: '—' }}
                    </td>

                    <td>
                        {{ $patient->cpf ?: '—' }}
                    </td>

                    <td class="text-end">
                        {{ $patient->appointments()->count() }}
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="5"
                        class="text-center py-5 text-secondary">

                        Nenhum paciente cadastrado.

                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    <div class="card-footer">
        {{ $patients->links() }}
    </div>

</div>


<div class="modal fade"
     id="patientModal"
     tabindex="-1">

    <div class="modal-dialog modal-lg">

        <form
            method="POST"
            action="{{ route('patients.store') }}"
            class="modal-content">

            @csrf

            <div class="modal-header">

                <div>
                    <h5 class="modal-title">
                        Novo paciente
                    </h5>

                    <small class="text-secondary">
                        Dados básicos para agendamento
                    </small>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body">

                <div class="row g-3">

                    <div class="col-md-8">

                        <label class="form-label">
                            Nome completo
                        </label>

                        <input
                            name="name"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            WhatsApp / telefone
                        </label>

                        <input
                            name="phone"
                            class="form-control"
                            required>

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
                            CPF
                        </label>

                        <input
                            name="cpf"
                            class="form-control">

                    </div>

                    <div class="col-md-3">

                        <label class="form-label">
                            Nascimento
                        </label>

                        <input
                            type="date"
                            name="birth_date"
                            class="form-control">

                    </div>

                    <div class="col-12">

                        <label class="form-label">
                            Observações
                        </label>

                        <textarea
                            name="notes"
                            rows="3"
                            class="form-control">
                        </textarea>

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal">

                    Cancelar

                </button>

                <button
                    class="btn btn-primary">

                    Salvar paciente

                </button>

            </div>

        </form>

    </div>

</div>

@stop
