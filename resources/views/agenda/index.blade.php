@extends('layouts.app')

@section('title', 'Agenda')

@section('content_header')

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3">

    <div>

        <h3 class="enfas-page-title">
            Agenda
        </h3>

        <p class="enfas-page-subtitle">
            Clique em um horário livre ou arraste um compromisso.
        </p>

    </div>

    <button
        id="newAppointmentBtn"
        class="btn btn-primary"
        data-bs-toggle="modal"
        data-bs-target="#appointmentModal">

        <i class="bi bi-plus-lg me-1"></i>

        Novo agendamento

    </button>

</div>

@stop


@section('content')

@if($errors->any())

    <div class="alert alert-danger">

        <strong>Não foi possível salvar:</strong>

        {{ $errors->first() }}

    </div>

@endif



<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Profissional</label>
                <select id="agendaFilterProfessional" class="form-select">
                    <option value="">Todos</option>
                    @foreach($professionals as $professional)
                        <option value="{{ $professional->id }}">{{ $professional->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="form-label">Serviço</label>
                <select id="agendaFilterService" class="form-select">
                    <option value="">Todos</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-3 col-md-6">
                <label class="form-label">Status</label>
                <select id="agendaFilterStatus" class="form-select">
                    <option value="">Todos</option>
                    <option value="awaiting_confirmation">Aguardando confirmação</option>
                    <option value="confirmed">Confirmado</option>
                    <option value="completed">Concluído</option>
                    <option value="cancelled">Cancelado</option>
                    <option value="no_show">Falta</option>
                </select>
            </div>

            <div class="col-lg-3 col-md-6 d-flex gap-2">
                <button id="agendaApplyFilters" type="button" class="btn btn-outline-primary flex-fill">
                    <i class="bi bi-funnel me-1"></i>Filtrar
                </button>
                <button type="button" class="btn btn-primary flex-fill" data-bs-toggle="modal" data-bs-target="#bestSlotModal">
                    <i class="bi bi-stars me-1"></i>Melhor horário
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card agenda-card">

    <div class="card-body">

        <div id="calendar"></div>

    </div>

</div>


<div class="modal fade"
     id="appointmentModal"
     tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-scrollable">

        <form
            method="POST"
            action="{{ route('appointments.store') }}"
            class="modal-content">

            @csrf

            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Novo agendamento
                    </h5>

                    <small class="text-secondary">
                        Atendimento interno
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

                    <div class="col-md-7">

                        <label class="form-label">
                            Paciente
                        </label>

                        <select
                            name="patient_id"
                            class="form-select"
                            required>

                            <option value="">
                                Selecione...
                            </option>

                            @foreach($patients as $patient)

                                <option value="{{ $patient->id }}">

                                    {{ $patient->name }}
                                    ·
                                    {{ $patient->phone }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="col-md-5">

                        <label class="form-label">
                            Data e hora
                        </label>

                        <input
                            id="appointmentStart"
                            type="datetime-local"
                            name="start_at"
                            class="form-control"
                            required>

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Serviço
                        </label>

                        <select
                            name="service_id"
                            class="form-select"
                            required>

                            <option value="">
                                Selecione...
                            </option>

                            @foreach($services as $service)

                                <option
                                    value="{{ $service->id }}"
                                    data-duration="{{ $service->duration_minutes }}">

                                    {{ $service->name }}
                                    ·
                                    {{ $service->duration_minutes }} min

                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Profissional
                        </label>

                        <select
                            name="professional_id"
                            class="form-select"
                            required>

                            <option value="">
                                Selecione...
                            </option>

                            @foreach($professionals as $professional)

                                <option value="{{ $professional->id }}">

                                    {{ $professional->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                <hr class="my-4">

                <div class="mb-3">
                    <h6 class="fw-semibold mb-1">Tipo de agendamento</h6>
                    <small class="text-secondary">Use retirada de medicamento quando o paciente vier apenas buscar medicação.</small>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tipo</label>
                        <select name="appointment_type" id="appointmentType" class="form-select" required>
                            <option value="care">Atendimento</option>
                            <option value="medication_pickup">Retirada de medicamento</option>
                        </select>
                    </div>
                </div>

                <div id="medicationPickupFields" class="row g-3 mt-1 d-none">
                    <div class="col-md-7">
                        <label class="form-label">Medicamento</label>
                        <input name="medication_name" id="medicationName" class="form-control" maxlength="255" placeholder="Nome do medicamento">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Quantidade / apresentação</label>
                        <input name="medication_quantity" id="medicationQuantity" class="form-control" maxlength="120" placeholder="Ex.: 2 caixas">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Orientações administrativas da retirada</label>
                        <textarea name="medication_notes" class="form-control" rows="3" placeholder="Informações de separação, retirada ou identificação. Não substitui orientação clínica."></textarea>
                    </div>
                </div>


                @if($customFields->isNotEmpty())

                    <hr class="my-4">

                    <div class="mb-3">

                        <h6 class="fw-semibold mb-1">
                            Informações adicionais
                        </h6>

                        <small class="text-secondary">
                            Campos personalizados deste agendamento
                        </small>

                    </div>

                    <div class="row g-3">

                        @foreach($customFields as $field)

                            <div class="{{ $field->field_type === 'textarea' ? 'col-12' : 'col-md-6' }}">

                                <label class="form-label">

                                    {{ $field->name }}

                                    @if($field->is_required)
                                        <span class="text-danger">*</span>
                                    @endif

                                </label>

                                @if($field->field_type === 'textarea')

                                    <textarea
                                        name="custom_fields[{{ $field->id }}]"
                                        class="form-control"
                                        rows="3"
                                        placeholder="{{ $field->placeholder }}"
                                        {{ $field->is_required ? 'required' : '' }}>
                                    </textarea>

                                @elseif($field->field_type === 'select')

                                    <select
                                        name="custom_fields[{{ $field->id }}]"
                                        class="form-select"
                                        {{ $field->is_required ? 'required' : '' }}>

                                        <option value="">
                                            Selecione...
                                        </option>

                                        @foreach($field->options ?? [] as $option)

                                            <option value="{{ $option }}">
                                                {{ $option }}
                                            </option>

                                        @endforeach

                                    </select>

                                @elseif($field->field_type === 'checkbox')

                                    <div class="form-check mt-2">

                                        <input
                                            type="checkbox"
                                            name="custom_fields[{{ $field->id }}]"
                                            value="1"
                                            class="form-check-input"
                                            id="cf_{{ $field->id }}">

                                        <label
                                            class="form-check-label"
                                            for="cf_{{ $field->id }}">

                                            Sim

                                        </label>

                                    </div>

                                @else

                                    <input
                                        type="{{ $field->field_type === 'number'
                                            ? 'number'
                                            : ($field->field_type === 'date'
                                                ? 'date'
                                                : 'text')
                                        }}"
                                        name="custom_fields[{{ $field->id }}]"
                                        class="form-control"
                                        placeholder="{{ $field->placeholder }}"
                                        {{ $field->is_required ? 'required' : '' }}>

                                @endif

                                @if($field->help_text)

                                    <small class="text-secondary">
                                        {{ $field->help_text }}
                                    </small>

                                @endif

                            </div>

                        @endforeach

                    </div>

                @endif



                <hr class="my-4">

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="recurrenceToggle">
                    <label class="form-check-label fw-semibold" for="recurrenceToggle">
                        Agendamento recorrente
                    </label>
                </div>

                <div id="recurrenceFields" class="row g-3 mt-1 d-none">
                    <div class="col-md-4">
                        <label class="form-label">Frequência</label>
                        <select class="form-select" id="recurrenceFrequency" name="frequency">
                            <option value="weekly">Semanal</option>
                            <option value="daily">Diário</option>
                            <option value="monthly">Mensal</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">A cada</label>
                        <input type="number" min="1" max="52" value="1" class="form-control" id="recurrenceInterval" name="interval">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Máx. ocorrências</label>
                        <input type="number" min="1" max="365" value="12" class="form-control" id="recurrenceMax" name="max_occurrences">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Data inicial</label>
                        <input type="date" class="form-control" id="recurrenceStartDate" name="starts_on">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Data final (opcional)</label>
                        <input type="date" class="form-control" id="recurrenceEndDate" name="ends_on">
                    </div>
                </div>


                <hr class="my-4">

                <div>

                    <label class="form-label">
                        Observações internas
                    </label>

                    <textarea
                        name="notes"
                        rows="3"
                        class="form-control"
                        placeholder="Informações visíveis apenas para a equipe">
                    </textarea>

                </div>


                <div class="enfas-confirm-preview mt-4">

                    <div class="enfas-confirm-preview-icon">

                        <i class="bi bi-whatsapp"></i>

                    </div>

                    <div>

                        <strong>
                            Confirmação automática
                        </strong>

                        <div class="small text-secondary">

                            Quando a integração da Meta estiver ativa,
                            este agendamento poderá solicitar confirmação
                            automaticamente.

                        </div>

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

                <button class="btn btn-primary">

                    <i class="bi bi-calendar-check me-1"></i>

                    Criar agendamento

                </button>

            </div>

        </form>

    </div>

</div>



<div class="modal fade" id="bestSlotModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Encontrar melhor horário</h5>
                    <small class="text-secondary">Busca disponibilidade real considerando agenda, pausas e bloqueios.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Profissional</label>
                        <select id="bestSlotProfessional" class="form-select">
                            <option value="">Selecione...</option>
                            @foreach($professionals as $professional)
                                <option value="{{ $professional->id }}">{{ $professional->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Serviço</label>
                        <select id="bestSlotService" class="form-select">
                            <option value="">Selecione...</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Período</label>
                        <select id="bestSlotPeriod" class="form-select">
                            <option value="">Qualquer</option>
                            <option value="morning">Manhã</option>
                            <option value="afternoon">Tarde</option>
                            <option value="evening">Noite</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-primary" onclick="findBestSlots()">
                        <i class="bi bi-search me-1"></i>Buscar horários
                    </button>
                </div>

                <div id="bestSlotResults" class="mt-4"></div>
            </div>
        </div>
    </div>
</div>

<button
    id="openAppointmentDetails"
    type="button"
    class="d-none"
    data-bs-toggle="offcanvas"
    data-bs-target="#appointmentDetails">
</button>


<div
    class="offcanvas offcanvas-end enfas-appointment-drawer"
    tabindex="-1"
    id="appointmentDetails">

    <div class="offcanvas-header">

        <div>

            <small class="text-secondary">
                AGENDAMENTO
            </small>

            <h5
                class="offcanvas-title"
                id="detailCode">
                —
            </h5>

        </div>

        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="offcanvas">
        </button>

    </div>


    <div class="offcanvas-body">

        <div
            id="detailStatus"
            class="mb-4">
        </div>


        <div class="enfas-detail-block">
            <small>Paciente</small>
            <strong id="detailPatient">—</strong>
            <span id="detailPhone">—</span>
            <span id="detailEmail">—</span>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <a id="detailWhatsappLink" class="btn btn-success btn-sm d-none" target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp me-1"></i>WhatsApp
                </a>

                <a id="detailPhoneLink" class="btn btn-outline-primary btn-sm d-none">
                    <i class="bi bi-telephone me-1"></i>Ligar
                </a>

                <a id="detailEmailLink" class="btn btn-outline-secondary btn-sm d-none">
                    <i class="bi bi-envelope me-1"></i>E-mail
                </a>

                <a id="detailJourneyLink" class="btn btn-outline-primary btn-sm d-none" target="_blank" rel="noopener">
                    <i class="bi bi-person-walking me-1"></i>Jornada
                </a>

                <button id="detailJourneyCopy" type="button" class="btn btn-outline-secondary btn-sm d-none" onclick="copyJourneyLink()">
                    <i class="bi bi-copy me-1"></i>Copiar link
                </button>

                <button type="button" class="btn btn-primary btn-sm" onclick="openPatientContactComposer()">
                    <i class="bi bi-chat-dots me-1"></i>Enviar mensagem
                </button>
            </div>
        </div>

        <div id="patientContactComposer" class="card border mt-3 d-none">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <strong>Contato com o paciente</strong>
                    <button type="button" class="btn-close" onclick="closePatientContactComposer()"></button>
                </div>

                <div class="mb-2">
                    <select id="patientContactPreset" class="form-select form-select-sm" onchange="applyPatientContactPreset()">
                        <option value="">Mensagem personalizada</option>
                        <option value="confirmation">Confirmar informações do agendamento</option>
                        <option value="orientation">Enviar orientações</option>
                        <option value="delay">Avisar atraso</option>
                        <option value="callback">Solicitar retorno</option>
                    </select>
                </div>

                <textarea id="patientContactMessage" class="form-control" rows="5" maxlength="4000" placeholder="Digite a mensagem para o paciente..."></textarea>

                <div class="d-flex justify-content-end mt-2">
                    <button id="patientContactSendBtn" type="button" class="btn btn-success btn-sm" onclick="sendPatientContact()">
                        <i class="bi bi-whatsapp me-1"></i>Enviar pelo WhatsApp
                    </button>
                </div>
            </div>
        </div>


        <div class="row g-3 mt-1">

            <div class="col-6">

                <div class="enfas-detail-block">

                    <small>Serviço</small>
                    <strong id="detailService">—</strong>

                </div>

            </div>

            <div class="col-6">

                <div class="enfas-detail-block">

                    <small>Profissional</small>
                    <strong id="detailProfessional">—</strong>

                </div>

            </div>

        </div>


        <div class="enfas-detail-block mt-3">

            <small>Horário</small>

            <strong id="detailDate">
                —
            </strong>

        </div>

        <div id="detailRgeaBlock" class="enfas-detail-block mt-3 d-none">
            <small>RGEA / Matrícula</small>
            <strong id="detailRgea">—</strong>
        </div>

        <div id="detailMedicationPickup" class="enfas-detail-block mt-3 d-none">
            <small>Retirada de medicamento</small>
            <strong id="detailMedicationName">—</strong>
            <span id="detailMedicationQuantity">—</span>
            <span id="detailPickupStatus">—</span>
            <div id="detailMedicationNotes" class="small text-secondary mt-2"></div>
        </div>


        <div
            id="detailCustomFields"
            class="mt-4">
        </div>


        <div class="d-flex flex-wrap gap-2 mt-4">

            <button
                class="btn btn-success btn-sm"
                onclick="setAppointmentStatus('confirmed')">

                <i class="bi bi-check2-circle me-1"></i>
                Confirmar

            </button>

            <button
                class="btn btn-primary btn-sm"
                onclick="setAppointmentStatus('completed')">

                Concluir

            </button>

            <button
                class="btn btn-outline-danger btn-sm"
                onclick="setAppointmentStatus('no_show')">

                Falta

            </button>

            <button
                class="btn btn-outline-secondary btn-sm"
                onclick="setAppointmentStatus('cancelled')">

                Cancelar

            </button>

        </div>


        <hr class="my-4">

        <div class="d-flex align-items-center justify-content-between">
            <h6 class="fw-semibold mb-0">Comunicação</h6>
            <small class="text-secondary">Últimas interações</small>
        </div>

        <div id="detailCommunications" class="mt-3"></div>

        <hr class="my-4">

        <h6 class="fw-semibold">
            Timeline
        </h6>

        <div
            id="detailTimeline"
            class="enfas-timeline mt-3">
        </div>

    </div>

</div>

@stop


@section('js')

<script src="{{ asset('vendor/fullcalendar/index.global.min.js') }}"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const calendarElement =
        document.getElementById('calendar');

    const csrf =
        document.querySelector('meta[name="csrf-token"]')
            .getAttribute('content');

    window.currentAppointmentId = null;
    window.currentAppointmentData = null;

    const appointmentType = document.getElementById('appointmentType');
    const medicationPickupFields = document.getElementById('medicationPickupFields');
    const medicationName = document.getElementById('medicationName');
    const medicationQuantity = document.getElementById('medicationQuantity');

    const syncAppointmentType = () => {
        const isPickup = appointmentType?.value === 'medication_pickup';
        medicationPickupFields?.classList.toggle('d-none', ! isPickup);

        if (medicationName) medicationName.required = isPickup;
        if (medicationQuantity) medicationQuantity.required = isPickup;
    };

    appointmentType?.addEventListener('change', syncAppointmentType);
    syncAppointmentType();


    const calendar = new FullCalendar.Calendar(
        calendarElement,
        {
            initialView: 'timeGridWeek',

            firstDay: 1,

            height: 'auto',

            nowIndicator: true,

            selectable: true,

            editable: true,

            eventInteractive: true,

            slotMinTime: '06:00:00',

            slotMaxTime: '22:00:00',

            slotDuration: '00:30:00',

            allDaySlot: false,

            expandRows: true,

            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },

            buttonText: {
                today: 'Hoje',
                month: 'Mês',
                week: 'Semana',
                day: 'Dia'
            },

            events: function(info, successCallback, failureCallback) {
                const params = new URLSearchParams({
                    start: info.startStr,
                    end: info.endStr
                });

                const professional = document.getElementById('agendaFilterProfessional')?.value;
                const service = document.getElementById('agendaFilterService')?.value;
                const status = document.getElementById('agendaFilterStatus')?.value;

                if (professional) params.set('professional_id', professional);
                if (service) params.set('service_id', service);
                if (status) params.set('status', status);

                fetch(@json(route('agenda.events')) + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                })
                .then(response => response.json())
                .then(successCallback)
                .catch(failureCallback);
            },

            select: function(info) {

                const input =
                    document.getElementById(
                        'appointmentStart'
                    );

                input.value =
                    info.startStr.substring(0, 16);

                document
                    .getElementById('newAppointmentBtn')
                    .click();
            },

            eventClick: async function(info) {

                await loadAppointment(
                    info.event.id
                );
            },

            eventDrop: async function(info) {

                if (! await moveAppointment(info)) {
                    info.revert();
                }
            },

            eventResize: async function(info) {

                if (! await moveAppointment(info)) {
                    info.revert();
                }
            }
        }
    );


    calendar.render();


    async function moveAppointment(info) {

        try {

            const response = await fetch(
                `/agenda/agendamentos/${info.event.id}/mover`,
                {
                    method: 'PATCH',

                    headers: {
                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'X-CSRF-TOKEN':
                            csrf
                    },

                    body: JSON.stringify({
                        start:
                            info.event.start.toISOString(),

                        end:
                            info.event.end
                                ? info.event.end.toISOString()
                                : null
                    })
                }
            );


            if (! response.ok) {

                const error =
                    await response.json();

                alert(
                    error.message
                    || Object.values(
                        error.errors || {}
                    ).flat()[0]
                    || 'Não foi possível reagendar.'
                );

                return false;
            }


            return true;

        } catch (error) {

            alert(
                'Falha ao atualizar o agendamento.'
            );

            return false;
        }
    }


    window.loadAppointment =
        async function(id) {

            const response =
                await fetch(
                    `/agendamentos/${id}`,
                    {
                        headers: {
                            'Accept':
                                'application/json'
                        }
                    }
                );

            if (! response.ok) {
                return;
            }

            const data =
                await response.json();

            const appointment =
                data.appointment;

            window.currentAppointmentId =
                appointment.id;

            window.currentAppointmentData = appointment;


            document.getElementById(
                'detailCode'
            ).textContent =
                appointment.code;


            document.getElementById(
                'detailStatus'
            ).innerHTML =
                `<span class="badge"
                    style="
                        background:${appointment.status_color};
                        color:white;
                    ">
                    ${appointment.status_label}
                 </span>`;


            document.getElementById(
                'detailPatient'
            ).textContent =
                appointment.patient;


            document.getElementById('detailPhone').textContent =
                appointment.phone || 'Sem telefone';

            document.getElementById('detailEmail').textContent =
                appointment.email || 'Sem e-mail';

            const whatsappLink = document.getElementById('detailWhatsappLink');
            const phoneLink = document.getElementById('detailPhoneLink');
            const emailLink = document.getElementById('detailEmailLink');
            const journeyLink = document.getElementById('detailJourneyLink');
            const journeyCopy = document.getElementById('detailJourneyCopy');

            whatsappLink.classList.toggle('d-none', ! appointment.whatsapp_link);
            phoneLink.classList.toggle('d-none', ! appointment.tel_link);
            emailLink.classList.toggle('d-none', ! appointment.email_link);
            journeyLink.classList.toggle('d-none', ! appointment.journey_url);
            journeyCopy.classList.toggle('d-none', ! appointment.journey_url);

            if (appointment.whatsapp_link) whatsappLink.href = appointment.whatsapp_link;
            if (appointment.tel_link) phoneLink.href = appointment.tel_link;
            if (appointment.email_link) emailLink.href = appointment.email_link;
            if (appointment.journey_url) journeyLink.href = appointment.journey_url;


            document.getElementById(
                'detailService'
            ).textContent =
                appointment.service;


            document.getElementById(
                'detailProfessional'
            ).textContent =
                appointment.professional;


            document.getElementById(
                'detailDate'
            ).textContent =
                `${appointment.start} – ${appointment.end}`;

            const rgeaBlock = document.getElementById('detailRgeaBlock');
            const rgea = document.getElementById('detailRgea');
            rgeaBlock.classList.toggle('d-none', ! appointment.patient_rgea);
            rgea.textContent = appointment.patient_rgea || '—';

            const medicationBlock = document.getElementById('detailMedicationPickup');
            const isMedicationPickup = appointment.appointment_type === 'medication_pickup';
            medicationBlock.classList.toggle('d-none', ! isMedicationPickup);

            if (isMedicationPickup) {
                const pickupLabels = {
                    scheduled: 'Agendada',
                    preparing: 'Em separação',
                    ready: 'Pronta para retirada',
                    collected: 'Retirada concluída',
                    not_collected: 'Não retirada',
                    cancelled: 'Cancelada'
                };

                document.getElementById('detailMedicationName').textContent =
                    appointment.medication_name || 'Medicamento não informado';

                document.getElementById('detailMedicationQuantity').textContent =
                    appointment.medication_quantity || 'Quantidade não informada';

                document.getElementById('detailPickupStatus').textContent =
                    'Status: ' + (pickupLabels[appointment.pickup_status] || 'Em acompanhamento');

                document.getElementById('detailMedicationNotes').textContent =
                    appointment.medication_notes || '';
            }


            const customContainer =
                document.getElementById(
                    'detailCustomFields'
                );

            customContainer.innerHTML = '';

            if (
                appointment.custom_fields.length
            ) {

                customContainer.innerHTML =
                    '<h6 class="fw-semibold mb-3">Informações adicionais</h6>';

                appointment.custom_fields
                    .forEach(field => {

                        const block =
                            document.createElement('div');

                        block.className =
                            'enfas-detail-block mb-2';

                        block.innerHTML =
                            `<small>${escapeHtml(field.name)}</small>
                             <strong>${escapeHtml(field.value)}</strong>`;

                        customContainer
                            .appendChild(block);
                    });
            }


            const communications =
                document.getElementById('detailCommunications');

            communications.innerHTML = '';

            if (!data.communications || data.communications.length === 0) {
                communications.innerHTML =
                    '<div class="text-secondary small">Nenhuma comunicação vinculada a este agendamento.</div>';
            } else {
                data.communications.forEach(message => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'border rounded-3 p-3 mb-2 bg-body-tertiary';

                    const direction =
                        message.direction === 'outbound'
                            ? 'Enviado'
                            : 'Recebido';

                    wrapper.innerHTML =
                        '<div class="d-flex justify-content-between gap-3">' +
                            '<strong class="small">' + escapeHtml(direction) + '</strong>' +
                            '<span class="badge text-bg-light border">' + escapeHtml(message.status || '-') + '</span>' +
                        '</div>' +
                        '<div class="small mt-2">' + escapeHtml(message.body || '') + '</div>' +
                        '<div class="text-secondary mt-2" style="font-size:.72rem;">' + escapeHtml(message.date || '') + '</div>';

                    communications.appendChild(wrapper);
                });
            }


            const timeline =
                document.getElementById(
                    'detailTimeline'
                );

            timeline.innerHTML = '';

            data.timeline.forEach(
                event => {

                    const item =
                        document.createElement('div');

                    item.className =
                        'enfas-timeline-item';

                    item.innerHTML =
                        `<div class="enfas-timeline-dot"></div>

                         <div>
                            <strong>
                                ${escapeHtml(event.title)}
                            </strong>

                            <div class="small text-secondary">
                                ${escapeHtml(event.description || '')}
                            </div>

                            <div class="enfas-timeline-meta">
                                ${escapeHtml(event.date)}
                                ${event.user
                                    ? ' · ' + escapeHtml(event.user)
                                    : ''
                                }
                            </div>
                         </div>`;

                    timeline.appendChild(item);
                }
            );


            document
                .getElementById(
                    'openAppointmentDetails'
                )
                .click();
        };


    window.setAppointmentStatus =
        async function(status) {

            if (! window.currentAppointmentId) {
                return;
            }

            if (
                status === 'cancelled'
                && ! confirm(
                    'Deseja realmente cancelar este agendamento?'
                )
            ) {
                return;
            }


            const response =
                await fetch(
                    `/agendamentos/${window.currentAppointmentId}/status`,
                    {
                        method: 'PATCH',

                        headers: {
                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json',

                            'X-CSRF-TOKEN':
                                csrf
                        },

                        body: JSON.stringify({
                            status: status
                        })
                    }
                );


            if (! response.ok) {
                alert(
                    'Não foi possível alterar o status.'
                );
                return;
            }


            calendar.refetchEvents();

            await loadAppointment(
                window.currentAppointmentId
            );
        };



    document.getElementById('agendaApplyFilters')?.addEventListener('click', function () {
        calendar.refetchEvents();
    });

    ['agendaFilterProfessional','agendaFilterService','agendaFilterStatus'].forEach(function(id) {
        document.getElementById(id)?.addEventListener('change', function () {
            calendar.refetchEvents();
        });
    });

    const recurrenceToggle = document.getElementById('recurrenceToggle');
    recurrenceToggle?.addEventListener('change', function () {
        document.getElementById('recurrenceFields')?.classList.toggle('d-none', !this.checked);
        const form = document.querySelector('#appointmentModal form');
        if (form) {
            form.action = this.checked
                ? @json(route('appointments.recurring.store'))
                : @json(route('appointments.store'));
        }
    });

    const appointmentStartInput = document.getElementById('appointmentStart');
    appointmentStartInput?.addEventListener('change', function () {
        if (! this.value) return;
        const date = this.value.slice(0,10);
        const time = this.value.slice(11,16);
        const startDate = document.getElementById('recurrenceStartDate');
        if (startDate) startDate.value = date;

        const form = document.querySelector('#appointmentModal form');
        if (! form) return;

        let hiddenTime = form.querySelector('input[name="time"]');
        if (! hiddenTime) {
            hiddenTime = document.createElement('input');
            hiddenTime.type = 'hidden';
            hiddenTime.name = 'time';
            form.appendChild(hiddenTime);
        }
        hiddenTime.value = time;
    });

    window.findBestSlots = async function() {
        const professional = document.getElementById('bestSlotProfessional').value;
        const service = document.getElementById('bestSlotService').value;
        const period = document.getElementById('bestSlotPeriod').value;
        const results = document.getElementById('bestSlotResults');

        if (!professional || !service) {
            results.innerHTML = '<div class="alert alert-warning mb-0">Selecione profissional e serviço.</div>';
            return;
        }

        results.innerHTML = '<div class="text-secondary"><span class="spinner-border spinner-border-sm me-2"></span>Buscando disponibilidade...</div>';

        const params = new URLSearchParams({
            professional_id: professional,
            service_id: service,
            limit: '8'
        });

        if (period) params.set('period', period);

        try {
            const response = await fetch(@json(route('agenda.best-slots')) + '?' + params.toString(), {
                headers: { 'Accept': 'application/json' }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Falha ao buscar horários.');
            }

            if (!data.slots.length) {
                results.innerHTML = '<div class="alert alert-light border mb-0">Nenhum horário encontrado nesse período.</div>';
                return;
            }

            results.innerHTML = '<div class="row g-2">' + data.slots.map(slot =>
                '<div class="col-md-6">' +
                    '<button type="button" class="btn btn-outline-primary w-100 text-start p-3" onclick="useBestSlot(\'' + slot.start + '\')">' +
                        '<i class="bi bi-calendar2-check me-2"></i>' + escapeHtml(slot.label) +
                    '</button>' +
                '</div>'
            ).join('') + '</div>';

        } catch (error) {
            results.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message) + '</div>';
        }
    };

    window.useBestSlot = function(start) {
        const modal = bootstrap.Modal.getInstance(document.getElementById('bestSlotModal'));
        modal?.hide();

        const value = start.replace(' ', 'T').slice(0,16);
        document.getElementById('appointmentStart').value = value;
        document.querySelector('select[name="professional_id"]').value =
            document.getElementById('bestSlotProfessional').value;
        document.querySelector('select[name="service_id"]').value =
            document.getElementById('bestSlotService').value;

        document.getElementById('newAppointmentBtn').click();
        document.getElementById('appointmentStart').dispatchEvent(new Event('change'));
    };

    window.openPatientContactComposer = function() {
        document.getElementById('patientContactComposer').classList.remove('d-none');
        document.getElementById('patientContactMessage').focus();
    };

    window.closePatientContactComposer = function() {
        document.getElementById('patientContactComposer').classList.add('d-none');
    };

    window.applyPatientContactPreset = function() {
        const preset = document.getElementById('patientContactPreset').value;
        const a = window.currentAppointmentData;

        if (!a) return;

        const messages = {
            confirmation:
                'Olá, ' + a.patient + '! Estamos entrando em contato sobre seu agendamento de ' +
                a.service + ' com ' + a.professional + ', em ' + a.start +
                '. Caso tenha alguma dúvida, estamos à disposição.',
            orientation:
                'Olá, ' + a.patient + '! Seguem as orientações referentes ao seu atendimento de ' +
                a.service + ' em ' + a.start + '. Em caso de dúvida, fale com nossa equipe.',
            delay:
                'Olá, ' + a.patient + '! Estamos entrando em contato para informar uma atualização no seu atendimento de hoje. Nossa equipe acompanha o seu agendamento e está à disposição.',
            callback:
                'Olá, ' + a.patient + '! Precisamos falar com você sobre o seu agendamento ' +
                a.code + '. Quando puder, responda esta mensagem ou entre em contato com nossa equipe.'
        };

        document.getElementById('patientContactMessage').value =
            messages[preset] || '';
    };

    window.sendPatientContact = async function() {
        if (! window.currentAppointmentId) return;

        const textarea = document.getElementById('patientContactMessage');
        const button = document.getElementById('patientContactSendBtn');
        const message = textarea.value.trim();

        if (! message) {
            alert('Digite uma mensagem para o paciente.');
            return;
        }

        button.disabled = true;
        const original = button.innerHTML;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando';

        try {
            const response = await fetch(
                '/agendamentos/' + window.currentAppointmentId + '/contato',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        channel: 'whatsapp',
                        message: message
                    })
                }
            );

            const data = await response.json();

            if (! response.ok) {
                const error =
                    data.message ||
                    Object.values(data.errors || {}).flat()[0] ||
                    'Não foi possível enviar a mensagem.';
                alert(error);
                return;
            }

            textarea.value = '';
            document.getElementById('patientContactPreset').value = '';
            closePatientContactComposer();
            await loadAppointment(window.currentAppointmentId);

        } catch (error) {
            alert('Falha ao enviar a mensagem para o paciente.');
        } finally {
            button.disabled = false;
            button.innerHTML = original;
        }
    };


    function copyJourneyLink() {
    const url = window.currentAppointmentData?.journey_url;

    if (!url) return;

    navigator.clipboard.writeText(url)
        .then(() => {
            const button = document.getElementById('detailJourneyCopy');
            const original = button.innerHTML;
            button.innerHTML = '<i class="bi bi-check2 me-1"></i>Copiado';
            setTimeout(() => button.innerHTML = original, 1600);
        });
}


function escapeHtml(value) {

        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

});

</script>

@stop
