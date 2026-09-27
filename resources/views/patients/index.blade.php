@extends('layouts.app')

@section('title', 'Pacientes')

@section('page_kicker','PACIENTES E RGEA')
@section('page_title','Pacientes')
@section('page_subtitle','Cadastro, matrícula RGEA, contatos e histórico de atendimento em uma única ficha.')

@section('page_actions')
<button
    class="btn btn-primary"
    data-bs-toggle="modal"
    data-bs-target="#patientModal">
    <i class="bi bi-person-plus me-1"></i>
    Novo paciente
</button>
@endsection


@section('content')

<div class="card">

    <div class="card-header justify-content-between gap-3">
        <div>
            <strong class="d-block">Base de pacientes</strong>
            <span class="small text-secondary">Pesquise por nome, telefone, CPF ou RGEA.</span>
        </div>

        <form
            method="GET"
            class="d-flex gap-2 ea-searchbar">

            <input
                type="search"
                name="q"
                value="{{ $search }}"
                class="form-control"
                placeholder="Nome, telefone, CPF ou RGEA"
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
                <th>RGEA</th>
                <th class="text-end">Agendamentos</th>
                <th class="text-end">Ações</th>
            </tr>
            </thead>

            <tbody>

            @forelse($patients as $patient)

                <tr>

                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="ea-avatar">
                                {{ strtoupper(substr($patient->name,0,1)) }}
                            </div>
                            <div class="min-w-0">
                                <strong class="d-block text-truncate">{{ $patient->name }}</strong>
                                <span class="small text-secondary">Paciente ENFAS</span>
                            </div>
                        </div>
                    </td>

                    <td>{{ $patient->phone }}</td>

                    <td>
                        {{ $patient->email ?: '—' }}
                    </td>

                    <td>
                        {{ $patient->cpf ?: '—' }}
                    </td>

                    <td>
                        @if($patient->rgea_number)
                            <span class="ea-registry-pill"><i class="bi bi-upc-scan"></i>{{ $patient->rgea_number }}</span>
                        @else
                            <span class="text-secondary">—</span>
                        @endif
                    </td>

                    <td class="text-end">
                        {{ $patient->appointments_count }}
                    </td>

                    <td class="text-end">
                        <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-person-lines-fill me-1"></i>Ver ficha
                        </a>
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="7"
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
            <input type="hidden" name="preferred_contact_channel" value="whatsapp">
            <input type="hidden" name="contact_consent" value="1">
            <input type="hidden" name="do_not_contact" value="0">

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
                            RGEA <span class="text-danger">*</span>
                        </label>
                        <input
                            name="rgea_number"
                            class="form-control"
                            value="{{ old('rgea_number') }}"
                            required
                            autocomplete="off"
                            placeholder="Informe o RGEA">
                        <div class="form-text">
                            Identificador gerado no sistema de origem. O Agenda ENFAS não cria RGEA.
                        </div>
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
                        <div class="ea-form-section">
                            <div>
                                <strong>Endereço</strong>
                                <span>Digite o CEP para preencher automaticamente.</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">CEP</label>
                        <input
                            name="postal_code"
                            class="form-control"
                            inputmode="numeric"
                            maxlength="9"
                            data-cep-autofill
                            placeholder="00000-000">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Logradouro</label>
                        <input name="address_line" class="form-control" placeholder="Rua, avenida...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Número</label>
                        <input name="address_number" class="form-control" placeholder="Número">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Complemento</label>
                        <input name="address_complement" class="form-control" placeholder="Apto, bloco, referência...">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Bairro</label>
                        <input name="neighborhood" class="form-control" placeholder="Bairro">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Cidade</label>
                        <input name="city" class="form-control" placeholder="Cidade">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">UF</label>
                        <input name="state" maxlength="2" class="form-control text-uppercase" placeholder="UF">
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
