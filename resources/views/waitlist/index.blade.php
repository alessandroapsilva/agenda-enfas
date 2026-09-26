@extends('layouts.app')

@section('title','Lista de Espera')
@section('page_kicker','AGENDA INTELIGENTE')
@section('page_title','Lista de Espera')
@section('page_subtitle','Pacientes aguardando uma oportunidade de horário compatível.')

@section('page_actions')
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#waitlistModal">
    <i class="bi bi-person-plus me-1"></i>Adicionar paciente
</button>
@endsection

@section('content')
<div class="row g-3 mb-4">
    @php
        $waiting = $entries->getCollection()->where('status','waiting')->count();
        $offered = $entries->getCollection()->where('status','offered')->count();
        $accepted = $entries->getCollection()->where('status','accepted')->count();
    @endphp
    @foreach([
        ['Aguardando',$waiting,'bi-hourglass-split'],
        ['Oferta enviada',$offered,'bi-send-check'],
        ['Convertidos',$accepted,'bi-check2-circle'],
    ] as $m)
        <div class="col-md-4">
            <div class="enfas-stat">
                <div class="enfas-stat-top">
                    <span class="enfas-stat-label">{{ $m[0] }}</span>
                    <span class="enfas-stat-icon"><i class="bi {{ $m[2] }}"></i></span>
                </div>
                <div class="enfas-stat-number">{{ $m[1] }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Serviço</th>
                    <th>Profissional</th>
                    <th>Preferência</th>
                    <th>Janela</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>
                        <strong>{{ $entry->patient?->displayName() }}</strong>
                        <div class="small text-muted">{{ $entry->patient?->phone }}</div>
                    </td>
                    <td>{{ $entry->service?->name }}</td>
                    <td>{{ $entry->professional?->name ?: 'Qualquer profissional' }}</td>
                    <td>
                        {{ match($entry->preferred_period) {
                            'morning'=>'Manhã',
                            'afternoon'=>'Tarde',
                            'evening'=>'Noite',
                            default=>'Qualquer período'
                        } }}
                    </td>
                    <td>
                        {{ $entry->earliest_date?->format('d/m/Y') ?: '—' }}
                        @if($entry->latest_date)
                            → {{ $entry->latest_date->format('d/m/Y') }}
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ match($entry->status) {
                            'waiting'=>'text-bg-warning',
                            'offered'=>'text-bg-primary',
                            'accepted'=>'text-bg-success',
                            'cancelled'=>'text-bg-secondary',
                            default=>'text-bg-light'
                        } }}">
                            {{ match($entry->status) {
                                'waiting'=>'Aguardando',
                                'offered'=>'Oferta enviada',
                                'accepted'=>'Convertido',
                                'cancelled'=>'Cancelado',
                                default=>ucfirst($entry->status)
                            } }}
                        </span>
                        @if($entry->offer_expires_at && $entry->status==='offered')
                            <div class="small text-muted mt-1">expira {{ $entry->offer_expires_at->format('H:i') }}</div>
                        @endif
                    </td>
                    <td class="text-end">
                        @if(in_array($entry->status,['waiting','offered'],true))
                            <form method="POST" action="{{ route('waitlist.cancel',$entry) }}">
                                @csrf @method('PATCH')
                                <button class="btn btn-outline-secondary btn-sm">Remover</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-5 text-muted">Nenhum paciente na lista de espera.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $entries->links() }}</div>
</div>

<div class="modal fade" id="waitlistModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('waitlist.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Adicionar à lista de espera</h5>
                    <small class="text-muted">O sistema poderá oferecer automaticamente uma vaga liberada.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Paciente</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}">{{ $patient->displayName() }} · {{ $patient->phone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Serviço</label>
                        <select name="service_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Profissional</label>
                        <select name="professional_id" class="form-select">
                            <option value="">Qualquer profissional</option>
                            @foreach($professionals as $professional)
                                <option value="{{ $professional->id }}">{{ $professional->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Período preferido</label>
                        <select name="preferred_period" class="form-select">
                            <option value="">Qualquer período</option>
                            <option value="morning">Manhã</option>
                            <option value="afternoon">Tarde</option>
                            <option value="evening">Noite</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">A partir de</label>
                        <input type="date" name="earliest_date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Até</label>
                        <input type="date" name="latest_date" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Adicionar à lista</button></div>
        </form>
    </div>
</div>
@endsection
