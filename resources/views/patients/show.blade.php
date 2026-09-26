@extends('layouts.app')

@section('title', $patient->displayName().' | Paciente')
@section('page_kicker', 'CRM DO PACIENTE')
@section('page_title', $patient->displayName())
@section('page_subtitle', 'Cadastro, histórico de atendimentos e comunicação em um só lugar.')

@section('page_actions')
<a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Pacientes
</a>
@endsection

@section('content')

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex gap-3 align-items-center">
                    <div class="rounded-4 bg-primary-subtle text-primary fw-bold d-grid" style="width:64px;height:64px;place-items:center;font-size:1.35rem;">
                        {{ strtoupper(substr($patient->displayName(),0,1)) }}
                    </div>
                    <div>
                        <h5 class="mb-1">{{ $patient->displayName() }}</h5>
                        @if($patient->preferred_name)
                            <div class="text-muted small">{{ $patient->name }}</div>
                        @endif
                        <span class="badge {{ $patient->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                            {{ $patient->is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>
                </div>

                <hr>

                <div class="vstack gap-2 small">
                    <div><i class="bi bi-whatsapp me-2 text-success"></i>{{ $patient->phone }}</div>
                    @if($patient->secondary_phone)<div><i class="bi bi-telephone me-2"></i>{{ $patient->secondary_phone }}</div>@endif
                    <div><i class="bi bi-envelope me-2"></i>{{ $patient->email ?: 'Sem e-mail' }}</div>
                    <div><i class="bi bi-person-vcard me-2"></i>{{ $patient->cpf ?: 'CPF não informado' }}</div>
                    @if($patient->birth_date)<div><i class="bi bi-cake2 me-2"></i>{{ $patient->birth_date->format('d/m/Y') }}</div>@endif
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    @if($patient->phone)
                        <a href="https://wa.me/{{ preg_replace('/\D+/', '', $patient->phone) }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
                            <i class="bi bi-whatsapp me-1"></i>Abrir WhatsApp
                        </a>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $patient->phone) }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-telephone me-1"></i>Ligar
                        </a>
                    @endif
                    @if($patient->email)
                        <a href="mailto:{{ $patient->email }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-envelope me-1"></i>E-mail
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Contato pelo sistema</h6>
            </div>
            <div class="card-body">
                @if($patient->do_not_contact || !$patient->contact_consent)
                    <div class="alert alert-warning small">
                        Contato automatizado desabilitado para este paciente.
                    </div>
                @else
                    <form method="POST" action="{{ route('patients.contact', $patient) }}">
                        @csrf
                        <textarea name="message" class="form-control" rows="5" maxlength="4000" placeholder="Digite uma mensagem para o paciente..." required></textarea>
                        <button class="btn btn-success w-100 mt-3">
                            <i class="bi bi-whatsapp me-1"></i>Enviar pelo WhatsApp
                        </button>
                    </form>
                @endif

                <div class="small text-muted mt-3">
                    Canal preferido: <strong>{{ ucfirst($patient->preferred_contact_channel) }}</strong>
                    @if($patient->last_contact_at)
                        · Último contato {{ $patient->last_contact_at->format('d/m/Y H:i') }}
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Editar cadastro</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('patients.update', $patient) }}" class="row g-3">
                    @csrf
                    @method('PATCH')

                    <div class="col-12">
                        <label class="form-label">Nome completo</label>
                        <input name="name" class="form-control" value="{{ $patient->name }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nome preferido</label>
                        <input name="preferred_name" class="form-control" value="{{ $patient->preferred_name }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">WhatsApp</label>
                        <input name="phone" class="form-control" value="{{ $patient->phone }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Telefone secundário</label>
                        <input name="secondary_phone" class="form-control" value="{{ $patient->secondary_phone }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" value="{{ $patient->email }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CPF</label>
                        <input name="cpf" class="form-control" value="{{ $patient->cpf }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nascimento</label>
                        <input type="date" name="birth_date" class="form-control" value="{{ $patient->birth_date?->format('Y-m-d') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Canal preferido</label>
                        <select name="preferred_contact_channel" class="form-select">
                            <option value="whatsapp" @selected($patient->preferred_contact_channel==='whatsapp')>WhatsApp</option>
                            <option value="phone" @selected($patient->preferred_contact_channel==='phone')>Telefone</option>
                            <option value="email" @selected($patient->preferred_contact_channel==='email')>E-mail</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Endereço</label>
                        <input name="address_line" class="form-control" value="{{ $patient->address_line }}" placeholder="Logradouro">
                    </div>
                    <div class="col-md-4"><input name="address_number" class="form-control" value="{{ $patient->address_number }}" placeholder="Número"></div>
                    <div class="col-md-8"><input name="address_complement" class="form-control" value="{{ $patient->address_complement }}" placeholder="Complemento"></div>
                    <div class="col-md-6"><input name="neighborhood" class="form-control" value="{{ $patient->neighborhood }}" placeholder="Bairro"></div>
                    <div class="col-md-6"><input name="city" class="form-control" value="{{ $patient->city }}" placeholder="Cidade"></div>
                    <div class="col-md-4"><input name="state" maxlength="2" class="form-control text-uppercase" value="{{ $patient->state }}" placeholder="UF"></div>
                    <div class="col-md-8"><input name="postal_code" class="form-control" value="{{ $patient->postal_code }}" placeholder="CEP"></div>

                    <div class="col-12">
                        <label class="form-label">Observações administrativas</label>
                        <textarea name="notes" class="form-control" rows="3">{{ $patient->notes }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-check">
                            <input type="hidden" name="contact_consent" value="0">
                            <input class="form-check-input" type="checkbox" name="contact_consent" value="1" @checked($patient->contact_consent)>
                            <span class="form-check-label">Permite contato pelo sistema</span>
                        </label>
                    </div>
                    <div class="col-12">
                        <label class="form-check">
                            <input type="hidden" name="do_not_contact" value="0">
                            <input class="form-check-input" type="checkbox" name="do_not_contact" value="1" @checked($patient->do_not_contact)>
                            <span class="form-check-label">Não contatar</span>
                        </label>
                    </div>

                    <div class="col-12">
                        <button class="btn btn-primary w-100">Salvar cadastro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="enfas-stat">
                    <div class="enfas-stat-label">Agendamentos</div>
                    <div class="enfas-stat-number">{{ $patient->appointments->count() }}</div>
                    <div class="enfas-stat-foot">últimos registros carregados</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="enfas-stat">
                    <div class="enfas-stat-label">Mensagens</div>
                    <div class="enfas-stat-number">{{ $patient->messages->count() }}</div>
                    <div class="enfas-stat-foot">últimas interações carregadas</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="enfas-stat">
                    <div class="enfas-stat-label">Contato</div>
                    <div class="enfas-stat-number" style="font-size:1.05rem;">
                        {{ $patient->do_not_contact ? 'Bloqueado' : ($patient->contact_consent ? 'Permitido' : 'Pendente') }}
                    </div>
                    <div class="enfas-stat-foot">preferência de comunicação</div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Histórico de agendamentos</h6>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Serviço</th>
                            <th>Profissional</th>
                            <th>Status</th>
                            <th>Código</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($patient->appointments as $appointment)
                        <tr>
                            <td>{{ $appointment->start_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $appointment->service?->name }}</td>
                            <td>{{ $appointment->professional?->name }}</td>
                            <td><span class="badge text-bg-{{ $appointment->statusBadge() }}">{{ $appointment->statusLabel() }}</span></td>
                            <td><strong>{{ $appointment->code }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted">Nenhum agendamento.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Comunicação</h6>
            </div>
            <div class="card-body">
                @forelse($patient->messages as $message)
                    <div class="border rounded-3 p-3 mb-2">
                        <div class="d-flex justify-content-between gap-3">
                            <strong class="small">{{ $message->direction === 'outbound' ? 'Enviado' : 'Recebido' }}</strong>
                            <span class="badge text-bg-light border">{{ $message->status }}</span>
                        </div>
                        <div class="small mt-2">{{ $message->body ?: 'Mensagem sem texto' }}</div>
                        <div class="text-muted mt-2" style="font-size:.72rem;">
                            {{ $message->created_at?->format('d/m/Y H:i') }}
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Nenhuma comunicação registrada.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
