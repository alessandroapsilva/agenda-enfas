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

            events: @json(route('agenda.events')),

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

            whatsappLink.classList.toggle('d-none', ! appointment.whatsapp_link);
            phoneLink.classList.toggle('d-none', ! appointment.tel_link);
            emailLink.classList.toggle('d-none', ! appointment.email_link);

            if (appointment.whatsapp_link) whatsappLink.href = appointment.whatsapp_link;
            if (appointment.tel_link) phoneLink.href = appointment.tel_link;
            if (appointment.email_link) emailLink.href = appointment.email_link;


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
