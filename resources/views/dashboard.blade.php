<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#09241c">
    <title>SIGH ENFAS — Gestão Hospitalar</title>
    <link rel="stylesheet" href="{{ asset('css/sigh-dashboard.css') }}">
</head>
<body>
<div class="mobile-overlay" data-overlay></div>
<div class="app-shell">
    <aside class="sidebar" data-sidebar>
        <div class="brand">
            <div class="brand-mark">S</div>
            <div class="brand-copy">
                <strong>SIGH</strong>
                <span>ENFAS Health Platform</span>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-label">Operação</div>
            <a class="nav-link active" href="{{ route('dashboard') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>
                Visão geral
            </a>
            <a class="nav-link" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
                Pacientes
            </a>
            <a class="nav-link" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Agenda
            </a>
            <a class="nav-link" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 2v5a2 2 0 0 1-2 2H3"/><path d="M14 2v5a2 2 0 0 0 2 2h5"/><path d="M5 9v4a7 7 0 0 0 14 0V9"/></svg>
                Atendimento
                @if($metrics['waiting'] > 0)<span class="badge">{{ $metrics['waiting'] }}</span>@endif
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Assistencial</div>
            <a class="nav-link" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12h6M12 9v6"/><rect x="4" y="3" width="16" height="18" rx="3"/></svg>
                Prontuário eletrônico
            </a>
            <a class="nav-link" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12h6M12 9v6"/><path d="M7 3h10l4 4v14H3V7z"/></svg>
                Prescrição
            </a>
            <a class="nav-link" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12h18M12 3v18"/><circle cx="12" cy="12" r="9"/></svg>
                Enfermagem
            </a>
            <a class="nav-link" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="3"/><path d="M7 8h10M7 12h6M7 16h4"/></svg>
                Internação e leitos
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Apoio</div>
            <a class="nav-link" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m10.5 20.5-7-7a4.95 4.95 0 0 1 7-7l7 7a4.95 4.95 0 0 1-7 7Z"/><path d="m8 8 8 8"/></svg>
                SIGH FAR
                @if($metrics['pending_dispensations'] > 0)<span class="badge">{{ $metrics['pending_dispensations'] }}</span>@endif
            </a>
            <a class="nav-link" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5V8l8-4 8 4v11.5"/><path d="M2 20h20M8 12h8M8 16h8"/></svg>
                Estoque e suprimentos
            </a>
            <a class="nav-link" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 3h6M10 9h4M8 3v5l-4 9a3 3 0 0 0 3 4h10a3 3 0 0 0 3-4l-4-9V3"/></svg>
                Laboratório / SADT
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Gestão</div>
            <a class="nav-link" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>
                BI e indicadores
            </a>
            <a class="nav-link" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.1A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3v-4h.1A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.1A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.13.37.34.7.6 1 .29.27.67.41 1.1.4h.1v4h-.1a1.7 1.7 0 0 0-1.7.6Z"/></svg>
                Administração
            </a>
        </div>

        <div class="sidebar-foot">
            <strong>SIGH ENFAS Enterprise</strong>
            <p>Núcleo hospitalar integrado, multiunidade e preparado para expansão assistencial.</p>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="menu-button" type="button" data-menu-open aria-label="Abrir menu">
                <svg viewBox="0 0 24 24" width="19" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>

            <div class="search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.4-3.4"/></svg>
                <input type="search" placeholder="Buscar paciente, prontuário, CPF, CNS ou atendimento...">
            </div>

            <div class="top-actions">
                <button class="icon-button" type="button" aria-label="Notificações">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                </button>
                <div class="profile">
                    <div class="avatar">EN</div>
                    <div class="profile-copy"><strong>ENFAS</strong><span>Ambiente de produção</span></div>
                </div>
            </div>
        </header>

        <section class="content">
            <div class="hero">
                <div>
                    <div class="eyebrow"><i></i> Operação assistencial em tempo real</div>
                    <h1>Visão geral do SIGH ENFAS</h1>
                    <p>{{ $generatedAt->translatedFormat('l, d \d\e F \d\e Y') }} · {{ $generatedAt->format('H:i') }}</p>
                </div>
                <div class="hero-actions">
                    <a class="btn" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
                        Novo paciente
                    </a>
                    <a class="btn primary" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h5"/></svg>
                        Novo atendimento
                    </a>
                </div>
            </div>

            <div class="metrics">
                <article class="metric">
                    <div class="metric-top"><span class="metric-label">Pacientes cadastrados</span><span class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M16 11a4 4 0 0 1 0 8"/></svg></span></div>
                    <div class="metric-value">{{ number_format($metrics['patients'], 0, ',', '.') }}</div>
                    <div class="metric-foot">Cadastro mestre de pacientes</div>
                </article>

                <article class="metric">
                    <div class="metric-top"><span class="metric-label">Agenda de hoje</span><span class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg></span></div>
                    <div class="metric-value">{{ number_format($metrics['appointments_today'], 0, ',', '.') }}</div>
                    <div class="metric-foot">{{ $metrics['waiting'] }} aguardando em fila</div>
                </article>

                <article class="metric">
                    <div class="metric-top"><span class="metric-label">Internações ativas</span><span class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 20V8M21 20V8M3 14h18M6 14v-3h4a3 3 0 0 1 3 3"/></svg></span></div>
                    <div class="metric-value">{{ number_format($metrics['active_admissions'], 0, ',', '.') }}</div>
                    <div class="metric-foot">{{ $metrics['available_beds'] }} leitos disponíveis</div>
                </article>

                <article class="metric">
                    <div class="metric-top"><span class="metric-label">Prescrições abertas</span><span class="metric-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 3h8l3 3v15H5V3z"/><path d="M9 11h6M12 8v6"/></svg></span></div>
                    <div class="metric-value">{{ number_format($metrics['open_prescriptions'], 0, ',', '.') }}</div>
                    <div class="metric-foot">Fluxo integrado ao SIGH FAR</div>
                </article>
            </div>

            <div class="layout">
                <div>
                    <section class="card">
                        <div class="card-head">
                            <div><h2>Fluxo assistencial</h2><p>Uma única jornada do paciente, do cadastro ao desfecho.</p></div>
                            <a class="link" href="#">Ver operação</a>
                        </div>
                        <div class="flow">
                            <div class="flow-track">
                                <div class="flow-step"><div class="index">01</div><strong>Matrícula</strong><span>Identificação e cadastro único</span></div>
                                <div class="flow-step"><div class="index">02</div><strong>Recepção</strong><span>Agenda, check-in e filas</span></div>
                                <div class="flow-step highlight"><div class="index">03</div><strong>Atendimento</strong><span>PEP, evolução e conduta</span></div>
                                <div class="flow-step"><div class="index">04</div><strong>Prescrição</strong><span>Medicamentos e cuidados</span></div>
                                <div class="flow-step"><div class="index">05</div><strong>Desfecho</strong><span>Alta, faturamento e indicadores</span></div>
                            </div>
                        </div>
                    </section>

                    <section class="card" style="margin-top:16px">
                        <div class="card-head">
                            <div><h2>Centrais do SIGH</h2><p>Acesso rápido aos principais módulos assistenciais.</p></div>
                            <a class="link" href="#">Todos os módulos</a>
                        </div>
                        <div class="module-grid">
                            <div class="module-card"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 12h6M12 9v6"/><rect x="4" y="3" width="16" height="18" rx="3"/></svg></div><strong>Prontuário eletrônico</strong><span>Timeline clínica, evoluções, diagnósticos e documentos.</span></div>
                            <div class="module-card"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12h18M12 3v18"/><circle cx="12" cy="12" r="9"/></svg></div><strong>Enfermagem</strong><span>Sinais vitais, registros assistenciais e checagens.</span></div>
                            <div class="module-card"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 20V8M21 20V8M3 14h18M6 14v-3h4a3 3 0 0 1 3 3"/></svg></div><strong>Internação e leitos</strong><span>Admissão, ocupação, transferências e alta.</span></div>
                            <div class="module-card"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 3h6M10 9h4M8 3v5l-4 9a3 3 0 0 0 3 4h10a3 3 0 0 0 3-4l-4-9V3"/></svg></div><strong>Laboratório / SADT</strong><span>Pedidos, coleta, execução, resultados e laudos.</span></div>
                            <div class="module-card"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></div><strong>BI assistencial</strong><span>Indicadores clínicos, ocupação, produção e custos.</span></div>
                            <div class="module-card"><div class="module-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h12v18H6z"/><path d="M9 7h6M9 11h6M9 15h4"/></svg></div><strong>Faturamento</strong><span>Contas, convênios, autorizações, guias e fechamento.</span></div>
                        </div>
                    </section>
                </div>

                <div>
                    <section class="card pharmacy-card">
                        <div class="card-head">
                            <div><h2>SIGH FAR</h2><p>Farmácia hospitalar e rastreabilidade.</p></div>
                            <a class="link" style="color:#77ddb1" href="#">Abrir farmácia</a>
                        </div>
                        <div class="pharmacy-stats">
                            <div class="pharmacy-stat"><span>Dispensações pendentes</span><strong>{{ $metrics['pending_dispensations'] }}</strong></div>
                            <div class="pharmacy-stat"><span>Itens em estoque mínimo</span><strong>{{ $metrics['low_stock'] }}</strong></div>
                            <div class="pharmacy-stat"><span>Lotes até 90 dias</span><strong>{{ $metrics['expiring_lots'] }}</strong></div>
                            <div class="pharmacy-stat"><span>Prescrições abertas</span><strong>{{ $metrics['open_prescriptions'] }}</strong></div>
                        </div>
                        <div class="system"><i></i> Núcleo de estoque e dispensação disponível</div>
                    </section>

                    <section class="card" style="margin-top:16px">
                        <div class="card-head">
                            <div><h2>Agenda de hoje</h2><p>Próximos atendimentos programados.</p></div>
                            <a class="link" href="#">Agenda completa</a>
                        </div>
                        @forelse($recentAppointments as $appointment)
                            <div class="list">
                                <div class="list-item">
                                    <div class="list-time">{{ \Illuminate\Support\Carbon::parse($appointment->starts_at)->format('H:i') }}</div>
                                    <div class="list-main">
                                        <strong>{{ $appointment->patient_name ?: 'Paciente sem identificação' }}</strong>
                                        <span>{{ ucfirst($appointment->type) }}</span>
                                    </div>
                                    <span class="status">{{ $appointment->status }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty">Nenhum atendimento agendado para hoje.</div>
                        @endforelse
                    </section>

                    <section class="card" style="margin-top:16px">
                        <div class="card-head">
                            <div><h2>Leitos</h2><p>Visão rápida da capacidade assistencial.</p></div>
                        </div>
                        <div class="pharmacy-stats" style="color:#17241f">
                            <div class="pharmacy-stat" style="background:#f8fbf9;border-color:#e6eeea"><span style="color:#82958d">Disponíveis</span><strong>{{ $metrics['available_beds'] }}</strong></div>
                            <div class="pharmacy-stat" style="background:#f8fbf9;border-color:#e6eeea"><span style="color:#82958d">Ocupados</span><strong>{{ $metrics['occupied_beds'] }}</strong></div>
                        </div>
                    </section>
                </div>
            </div>

            <footer class="footer">
                <span>SIGH ENFAS · Sistema de Informação e Gestão Hospitalar</span>
                <span>Ambiente seguro · {{ config('app.env') }} · Laravel {{ app()->version() }}</span>
            </footer>
        </section>
    </main>
</div>
<script src="{{ asset('js/sigh-dashboard.js') }}"></script>
</body>
</html>
