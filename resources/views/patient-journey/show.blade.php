<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Jornada do paciente | ENFAS Agenda</title>

    @vite([
        'resources/css/adminlte.css',
        'resources/css/enfas-agenda.css',
        'resources/js/adminlte.js'
    ])

    <style>
        body{background:#f5f7fb}
        .pj-shell{max-width:980px;margin:0 auto;padding:28px 18px 48px}
        .pj-brand{display:flex;align-items:center;gap:12px;margin-bottom:24px}
        .pj-brand img{width:44px;height:44px;border-radius:14px;box-shadow:0 10px 24px rgba(37,99,235,.18)}
        .pj-brand strong{display:block;font-size:1rem;line-height:1.1}
        .pj-brand span{display:block;color:#64748b;font-size:.78rem;margin-top:3px}
        .pj-hero{padding:30px;border-radius:26px;color:#fff;background:linear-gradient(135deg,#0f172a,#1e3a8a 65%,#2563eb);box-shadow:0 24px 60px rgba(15,23,42,.14)}
        .pj-kicker{font-size:.68rem;font-weight:800;letter-spacing:.14em;color:#bfdbfe}
        .pj-title{font-size:clamp(1.7rem,4vw,2.65rem);font-weight:800;letter-spacing:-.045em;margin:8px 0 10px}
        .pj-sub{color:#dbeafe;max-width:700px;margin:0}
        .pj-grid{display:grid;grid-template-columns:1.35fr .8fr;gap:20px;margin-top:20px}
        .pj-card{border:1px solid #e4eaf2;border-radius:20px;background:#fff;box-shadow:0 12px 32px rgba(15,23,42,.05)}
        .pj-card-body{padding:22px}
        .pj-label{color:#64748b;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
        .pj-value{color:#0f172a;font-weight:750;margin-top:4px}
        .pj-detail{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        .pj-detail>div{padding:14px;border-radius:14px;background:#f8fafc;border:1px solid #eef2f7}
        .pj-status{display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;font-size:.76rem;font-weight:800}
        .pj-status i{font-size:.7rem}
        .pj-actions{display:grid;gap:10px}
        .pj-actions .btn{min-height:46px}
        .pj-section-title{font-size:.9rem;font-weight:800;margin:0 0 12px}
        .pj-note{padding:14px;border-radius:14px;background:#f8fafc;border:1px solid #edf2f7;color:#475569;font-size:.86rem;white-space:pre-line}
        .pj-success{border-color:#bbf7d0;background:#f0fdf4;color:#166534}
        .pj-muted{color:#64748b;font-size:.82rem}
        .pj-nps{display:grid;grid-template-columns:repeat(11,1fr);gap:5px}
        .pj-nps label{cursor:pointer}
        .pj-nps input{display:none}
        .pj-nps span{height:36px;display:grid;place-items:center;border:1px solid #dbe3ee;border-radius:9px;font-weight:700;font-size:.78rem;background:#fff}
        .pj-nps input:checked+span{color:#fff;background:#2563eb;border-color:#2563eb}
        .pj-stars{display:flex;gap:6px;flex-direction:row-reverse;justify-content:flex-end}
        .pj-stars input{display:none}
        .pj-stars label{cursor:pointer;font-size:2rem;line-height:1;color:#cbd5e1;transition:.15s ease}
        .pj-stars input:checked~label,.pj-stars label:hover,.pj-stars label:hover~label{color:#f59e0b;transform:translateY(-1px)}
        .pj-progress{display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin-top:20px}
        .pj-step{position:relative;padding:12px 8px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;text-align:center}
        .pj-step i{display:grid;place-items:center;width:32px;height:32px;margin:0 auto 6px;border-radius:50%;background:#f1f5f9;color:#64748b}
        .pj-step strong{display:block;font-size:.72rem;color:#64748b}
        .pj-step.is-done{border-color:#bfdbfe;background:#eff6ff}
        .pj-step.is-done i{background:#2563eb;color:#fff}
        .pj-step.is-done strong{color:#1d4ed8}
        .pj-step.is-current{box-shadow:0 0 0 3px rgba(37,99,235,.08)}

        .pj-slot{cursor:pointer}
        .pj-slot input{display:none}
        .pj-slot span{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 13px;border:1px solid #dbe3ee;border-radius:12px;background:#fff;transition:.15s ease}
        .pj-slot span strong{font-size:.84rem}
        .pj-slot span small{color:#64748b;font-weight:700}
        .pj-slot input:checked+span{border-color:#2563eb;background:#eff6ff;box-shadow:0 0 0 3px rgba(37,99,235,.08)}
        @media(max-width:780px){.pj-grid{grid-template-columns:1fr}.pj-detail{grid-template-columns:1fr}.pj-hero{padding:23px}.pj-nps{grid-template-columns:repeat(6,1fr)}.pj-progress{grid-template-columns:repeat(3,1fr)}}
    </style>
</head>
<body>
@php
    $statusClass = match($appointment->status) {
        'confirmed' => 'text-bg-success',
        'completed' => 'text-bg-primary',
        'cancelled' => 'text-bg-secondary',
        'no_show' => 'text-bg-danger',
        default => 'text-bg-warning',
    };
@endphp

<div class="pj-shell">
    <div class="pj-brand">
        <img src="{{ asset('assets/brand/enfas-agenda.svg') }}" alt="ENFAS Agenda">
        <div>
            <strong>ENFAS Agenda</strong>
            <span>Enfermagem Alessandro Silva</span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-3">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-3">{{ $errors->first() }}</div>
    @endif

    <section class="pj-hero">
        <div class="pj-kicker">SUA JORNADA DE ATENDIMENTO</div>
        <h1 class="pj-title">Olá, {{ $appointment->patient?->preferred_name ?: $appointment->patient?->name }}.</h1>
        <p class="pj-sub">
            Aqui você acompanha seu atendimento, confirma presença, adiciona à agenda,
            consulta orientações e realiza seu check-in.
        </p>
    </section>

    @php
        $journeySteps = [
            ['Agendado', true, 'bi-calendar-check'],
            ['Confirmado', (bool) $appointment->confirmed_at || in_array($appointment->status,['confirmed','completed'],true), 'bi-check2-circle'],
            ['Check-in', (bool) $appointment->check_in_completed_at, 'bi-qr-code-scan'],
            ['Concluído', $appointment->status === 'completed', 'bi-person-check'],
            ['Avaliação', (bool) $appointment->satisfaction_at, 'bi-chat-square-heart'],
            ['Retorno', (bool) $appointment->return_due_at, 'bi-arrow-repeat'],
        ];

        $lastDone = collect($journeySteps)
            ->map(fn ($step, $index) => $step[1] ? $index : null)
            ->filter(fn ($index) => $index !== null)
            ->max() ?? 0;
    @endphp

    <div class="pj-progress">
        @foreach($journeySteps as $index => $step)
            <div class="pj-step {{ $step[1] ? 'is-done' : '' }} {{ $index === $lastDone ? 'is-current' : '' }}">
                <i class="bi {{ $step[2] }}"></i>
                <strong>{{ $step[0] }}</strong>
            </div>
        @endforeach
    </div>

    <div class="pj-grid">
        <main class="d-grid gap-3">
            <section class="pj-card">
                <div class="pj-card-body">
                    <div class="d-flex justify-content-between gap-3 align-items-start mb-4">
                        <div>
                            <div class="pj-label">Atendimento</div>
                            <div class="pj-value fs-5">{{ $appointment->service?->name }}</div>
                        </div>
                        <span class="pj-status {{ $statusClass }}">
                            <i class="bi bi-circle-fill"></i>
                            {{ $appointment->statusLabel() }}
                        </span>
                    </div>

                    <div class="pj-detail">
                        <div>
                            <div class="pj-label">Data</div>
                            <div class="pj-value">{{ $appointment->start_at->translatedFormat('d \d\e F \d\e Y') }}</div>
                        </div>
                        <div>
                            <div class="pj-label">Horário</div>
                            <div class="pj-value">{{ $appointment->start_at->format('H:i') }} às {{ $appointment->end_at->format('H:i') }}</div>
                        </div>
                        <div>
                            <div class="pj-label">Profissional</div>
                            <div class="pj-value">{{ $appointment->professional?->name }}</div>
                        </div>
                        <div>
                            <div class="pj-label">Código</div>
                            <div class="pj-value">{{ $appointment->code }}</div>
                        </div>
                        @if($appointment->location)
                            <div>
                                <div class="pj-label">Unidade / Local</div>
                                <div class="pj-value">{{ $appointment->location->name }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            @if($appointment->location)
                <section class="pj-card">
                    <div class="pj-card-body">
                        <h2 class="pj-section-title"><i class="bi bi-geo-alt me-2 text-primary"></i>Onde será seu atendimento</h2>
                        <div class="pj-value fs-6">{{ $appointment->location->name }}</div>

                        @if($appointment->location->fullAddress())
                            <div class="pj-muted mt-1">{{ $appointment->location->fullAddress() }}</div>
                        @endif

                        @if($appointment->location->patient_instructions)
                            <div class="pj-note mt-3">{{ $appointment->location->patient_instructions }}</div>
                        @endif
                    </div>
                </section>
            @endif

            @if($appointment->appointment_type === 'medication_pickup')
                <section class="pj-card">
                    <div class="pj-card-body">
                        <h2 class="pj-section-title"><i class="bi bi-capsule me-2 text-primary"></i>Retirada de medicamento</h2>

                        <div class="pj-detail">
                            <div>
                                <div class="pj-label">Medicamento</div>
                                <div class="pj-value">{{ $appointment->medication_name }}</div>
                            </div>
                            <div>
                                <div class="pj-label">Quantidade</div>
                                <div class="pj-value">{{ $appointment->medication_quantity }}</div>
                            </div>
                        </div>

                        @if($appointment->medication_notes)
                            <div class="pj-note mt-3">{{ $appointment->medication_notes }}</div>
                        @endif

                    </div>
                </section>
            @endif

            @if($appointment->service?->required_documents || $appointment->service?->preparation_instructions)
                <section class="pj-card">
                    <div class="pj-card-body">
                        <h2 class="pj-section-title"><i class="bi bi-clipboard2-check me-2 text-primary"></i>Antes do atendimento</h2>

                        @if($appointment->service?->arrival_minutes)
                            <div class="pj-note mb-3">
                                <strong>Chegada recomendada:</strong>
                                {{ $appointment->service->arrival_minutes }} minutos antes.
                            </div>
                        @endif

                        @if($appointment->service?->required_documents)
                            <div class="mb-3">
                                <div class="pj-label mb-2">Documentos necessários</div>
                                <div class="pj-note">{{ $appointment->service->required_documents }}</div>
                            </div>
                        @endif

                        @if($appointment->service?->preparation_instructions)
                            <div>
                                <div class="pj-label mb-2">Preparo / orientações</div>
                                <div class="pj-note">{{ $appointment->service->preparation_instructions }}</div>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            @if($appointment->status === 'completed' && $appointment->service?->aftercare_instructions)
                <section class="pj-card">
                    <div class="pj-card-body">
                        <h2 class="pj-section-title"><i class="bi bi-heart-pulse me-2 text-primary"></i>Após o atendimento</h2>
                        <div class="pj-note">{{ $appointment->service->aftercare_instructions }}</div>
                    </div>
                </section>
            @endif

            @if($appointment->status === 'completed')
                <section class="pj-card">
                    <div class="pj-card-body">
                        <h2 class="pj-section-title">Como foi sua experiência?</h2>

                        @if($appointment->satisfaction_at)
                            <div class="pj-note pj-success">
                                <div class="mb-1">
                                    @for($star=1;$star<=5;$star++)
                                        <i class="bi {{ $star <= (int)$appointment->satisfaction_stars ? 'bi-star-fill' : 'bi-star' }} text-warning"></i>
                                    @endfor
                                </div>
                                NPS <strong>{{ $appointment->satisfaction_score }}/10</strong>.
                                Obrigado por compartilhar sua experiência.
                            </div>
                        @else
                            <form method="POST" action="{{ route('patient-journey.satisfaction',$appointment->public_token) }}">
                                @csrf

                                <div class="mb-4">
                                    <div class="pj-label mb-2">Como você avalia sua experiência?</div>
                                    <div class="pj-stars" aria-label="Avaliação por estrelas">
                                        @for($star=5;$star>=1;$star--)
                                            <input id="star{{ $star }}" type="radio" name="stars" value="{{ $star }}" required>
                                            <label for="star{{ $star }}" title="{{ $star }} estrela(s)">★</label>
                                        @endfor
                                    </div>
                                </div>

                                <p class="pj-muted">De 0 a 10, quanto você recomendaria nosso atendimento?</p>
                                <div class="pj-nps mb-3">
                                    @for($score=0;$score<=10;$score++)
                                        <label>
                                            <input type="radio" name="score" value="{{ $score }}" required>
                                            <span>{{ $score }}</span>
                                        </label>
                                    @endfor
                                </div>
                                <textarea name="comment" class="form-control mb-3" rows="3" placeholder="Se quiser, conte o que podemos melhorar."></textarea>
                                <button class="btn btn-primary">Enviar avaliação</button>
                            </form>
                        @endif
                    </div>
                </section>
            @endif
        </main>

        <aside class="d-grid gap-3 align-content-start">
            <section class="pj-card">
                <div class="pj-card-body">
                    <h2 class="pj-section-title">Ações rápidas</h2>

                    <div class="pj-actions">
                        @if(!in_array($appointment->status,['completed','cancelled','no_show'],true))
                            @if($appointment->status !== 'confirmed')
                                <form method="POST" action="{{ route('patient-journey.confirm',$appointment->public_token) }}">
                                    @csrf
                                    <button class="btn btn-success w-100">
                                        <i class="bi bi-check2-circle"></i>Confirmar presença
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('patient-journey.calendar',$appointment->public_token) }}" class="btn btn-outline-primary">
                                <i class="bi bi-calendar-plus"></i>Adicionar ao calendário
                            </a>

                            @if($appointment->service?->allow_online_reschedule && count($rescheduleSlots))
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#rescheduleBox">
                                    <i class="bi bi-arrow-repeat"></i>Reagendar atendimento
                                </button>

                                <div class="collapse" id="rescheduleBox">
                                    <form method="POST" action="{{ route('patient-journey.reschedule',$appointment->public_token) }}" class="border rounded-4 p-3 bg-light">
                                        @csrf
                                        <div class="pj-label mb-2">Próximos horários disponíveis</div>

                                        <div class="d-grid gap-2 mb-3">
                                            @foreach($rescheduleSlots as $slot)
                                                <label class="pj-slot">
                                                    <input type="radio" name="start_at" value="{{ $slot['start'] }}" required>
                                                    <span>
                                                        <strong>{{ \Carbon\Carbon::parse($slot['start'])->translatedFormat('D, d/m') }}</strong>
                                                        <small>{{ \Carbon\Carbon::parse($slot['start'])->format('H:i') }}</small>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>

                                        <button class="btn btn-primary w-100">
                                            <i class="bi bi-check2"></i>Confirmar novo horário
                                        </button>
                                    </form>
                                </div>
                            @endif

                            @if($appointment->status === 'confirmed')
                                @if($appointment->check_in_completed_at)
                                    <div class="pj-note pj-success text-center">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Check-in realizado
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('patient-journey.check-in',$appointment->public_token) }}">
                                        @csrf
                                        <button class="btn btn-outline-success w-100">
                                            <i class="bi bi-qr-code-scan"></i>Fazer check-in
                                        </button>
                                    </form>
                                @endif
                            @endif

                            @if($appointment->telehealth_url)
                                <a href="{{ $appointment->telehealth_url }}" rel="noopener noreferrer" class="btn btn-outline-dark">
                                    <i class="bi bi-camera-video"></i>Entrar na teleconsulta
                                </a>
                            @endif

                            <button class="btn btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#cancelBox">
                                <i class="bi bi-x-circle"></i>Cancelar atendimento
                            </button>

                            <div class="collapse" id="cancelBox">
                                <form method="POST" action="{{ route('patient-journey.cancel',$appointment->public_token) }}" class="border rounded-4 p-3 bg-light">
                                    @csrf
                                    <label class="form-label small fw-semibold">Motivo do cancelamento</label>
                                    <textarea name="reason" class="form-control mb-2" rows="3" placeholder="Opcional"></textarea>
                                    <button class="btn btn-danger w-100">Confirmar cancelamento</button>
                                </form>
                            </div>
                        @else
                            <div class="pj-note">
                                Este atendimento está como <strong>{{ mb_strtolower($appointment->statusLabel()) }}</strong>.
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="pj-card">
                <div class="pj-card-body">
                    <h2 class="pj-section-title">Precisa de ajuda?</h2>
                    <p class="pj-muted mb-3">Nossa equipe pode orientar você sobre horários, preparo ou reagendamento.</p>
                    <a href="https://wa.me/5511978753868" class="btn btn-success w-100" rel="noopener noreferrer">
                        <i class="bi bi-whatsapp"></i>Falar no WhatsApp
                    </a>
                </div>
            </section>
        </aside>
    </div>
</div>
</body>
</html>
