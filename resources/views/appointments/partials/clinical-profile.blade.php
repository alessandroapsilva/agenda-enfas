<div class="card mt-4">
    <div class="card-header d-flex align-items-center justify-content-between gap-3">
        <div>
            <strong class="d-block">Perfil clínico longitudinal</strong>
            <span class="small text-secondary">Informações que acompanham o paciente entre atendimentos.</span>
        </div>
        <span class="badge text-bg-light border">Paciente {{ $appointment->patient->rgea_number ?: '#'.$appointment->patient_id }}</span>
    </div>

    <div class="card-body">
        <div class="accordion" id="clinicalProfileAccordion{{ $appointment->id }}">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#history{{ $appointment->id }}">
                        <i class="bi bi-journal-text me-2"></i>Antecedentes e história clínica
                    </button>
                </h2>
                <div id="history{{ $appointment->id }}" class="accordion-collapse collapse show" data-bs-parent="#clinicalProfileAccordion{{ $appointment->id }}">
                    <div class="accordion-body">
                        @can('records.manage')
                        <form method="POST" action="{{ route('clinical-profile.history.update',$appointment) }}">
                            @csrf @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Condições / antecedentes relevantes</label>
                                    <textarea name="chronic_conditions" class="form-control" rows="4">{{ old('chronic_conditions',$clinicalHistory->chronic_conditions) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Cirurgias / procedimentos prévios</label>
                                    <textarea name="surgeries" class="form-control" rows="4">{{ old('surgeries',$clinicalHistory->surgeries) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Histórico familiar</label>
                                    <textarea name="family_history" class="form-control" rows="4">{{ old('family_history',$clinicalHistory->family_history) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">História social / hábitos</label>
                                    <textarea name="social_history" class="form-control" rows="4">{{ old('social_history',$clinicalHistory->social_history) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Imunizações</label>
                                    <textarea name="immunizations" class="form-control" rows="3">{{ old('immunizations',$clinicalHistory->immunizations) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Outros antecedentes</label>
                                    <textarea name="other_history" class="form-control" rows="3">{{ old('other_history',$clinicalHistory->other_history) }}</textarea>
                                </div>
                            </div>
                            <button class="btn btn-light border mt-3"><i class="bi bi-save me-1"></i>Salvar antecedentes</button>
                        </form>
                        @else
                            @foreach([
                                'Condições / antecedentes'=>$clinicalHistory->chronic_conditions,
                                'Cirurgias / procedimentos'=>$clinicalHistory->surgeries,
                                'Histórico familiar'=>$clinicalHistory->family_history,
                                'História social / hábitos'=>$clinicalHistory->social_history,
                                'Imunizações'=>$clinicalHistory->immunizations,
                                'Outros'=>$clinicalHistory->other_history,
                            ] as $label=>$value)
                                @if($value)
                                    <div class="mb-3"><strong class="d-block">{{ $label }}</strong><span>{{ $value }}</span></div>
                                @endif
                            @endforeach
                        @endcan
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#allergies{{ $appointment->id }}">
                        <i class="bi bi-exclamation-triangle me-2"></i>Alergias
                        @if($allergies->where('status','active')->count())
                            <span class="badge text-bg-danger ms-2">{{ $allergies->where('status','active')->count() }}</span>
                        @endif
                    </button>
                </h2>
                <div id="allergies{{ $appointment->id }}" class="accordion-collapse collapse" data-bs-parent="#clinicalProfileAccordion{{ $appointment->id }}">
                    <div class="accordion-body">
                        <div class="table-responsive mb-3">
                            <table class="table align-middle">
                                <thead><tr><th>Substância</th><th>Reação</th><th>Gravidade</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                @forelse($allergies as $allergy)
                                    <tr>
                                        <td><strong>{{ $allergy->substance }}</strong></td>
                                        <td>{{ $allergy->reaction ?: '—' }}</td>
                                        <td>{{ match($allergy->severity) {'mild'=>'Leve','moderate'=>'Moderada','severe'=>'Grave',default=>'Não informada'} }}</td>
                                        <td>
                                            @if($allergy->status === 'active')
                                                <span class="badge text-bg-danger">Ativa</span>
                                            @else
                                                <span class="badge text-bg-secondary">Resolvida</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($allergy->status === 'active')
                                            @can('records.manage')
                                            <form method="POST" action="{{ route('clinical-profile.allergies.resolve',[$appointment,$allergy]) }}">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-sm btn-light border">Resolver</button>
                                            </form>
                                            @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><span class="text-secondary">Nenhuma alergia registrada.</span></td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        @can('records.manage')
                        <form method="POST" action="{{ route('clinical-profile.allergies.store',$appointment) }}" class="border rounded p-3">
                            @csrf
                            <strong class="d-block mb-3">Registrar alergia</strong>
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">Substância *</label><input name="substance" class="form-control" required maxlength="180"></div>
                                <div class="col-md-4"><label class="form-label">Reação</label><input name="reaction" class="form-control" maxlength="2000"></div>
                                <div class="col-md-4">
                                    <label class="form-label">Gravidade</label>
                                    <select name="severity" class="form-select">
                                        <option value="unknown">Não informada</option>
                                        <option value="mild">Leve</option>
                                        <option value="moderate">Moderada</option>
                                        <option value="severe">Grave</option>
                                    </select>
                                </div>
                                <div class="col-12"><label class="form-label">Observações</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                            </div>
                            <button class="btn btn-primary mt-3"><i class="bi bi-plus-lg me-1"></i>Adicionar alergia</button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#problems{{ $appointment->id }}">
                        <i class="bi bi-clipboard2-pulse me-2"></i>Lista de problemas / diagnósticos
                        @if($problems->where('status','active')->count())
                            <span class="badge text-bg-primary ms-2">{{ $problems->where('status','active')->count() }}</span>
                        @endif
                    </button>
                </h2>
                <div id="problems{{ $appointment->id }}" class="accordion-collapse collapse" data-bs-parent="#clinicalProfileAccordion{{ $appointment->id }}">
                    <div class="accordion-body">
                        <div class="table-responsive mb-3">
                            <table class="table align-middle">
                                <thead><tr><th>Descrição</th><th>Código</th><th>Início</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                @forelse($problems as $problem)
                                    <tr>
                                        <td><strong>{{ $problem->description }}</strong><div class="small text-secondary">{{ $problem->notes }}</div></td>
                                        <td>{{ $problem->code ? trim(($problem->code_system ?: '').' '.$problem->code) : '—' }}</td>
                                        <td>{{ $problem->onset_date?->format('d/m/Y') ?: '—' }}</td>
                                        <td>@if($problem->status==='active')<span class="badge text-bg-primary">Ativo</span>@else<span class="badge text-bg-secondary">Resolvido</span>@endif</td>
                                        <td class="text-end">
                                            @if($problem->status==='active')
                                            @can('records.manage')
                                            <form method="POST" action="{{ route('clinical-profile.problems.resolve',[$appointment,$problem]) }}">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-sm btn-light border">Resolver</button>
                                            </form>
                                            @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><span class="text-secondary">Nenhum problema clínico registrado.</span></td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        @can('records.manage')
                        <form method="POST" action="{{ route('clinical-profile.problems.store',$appointment) }}" class="border rounded p-3">
                            @csrf
                            <strong class="d-block mb-3">Adicionar problema clínico</strong>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label">Descrição *</label><input name="description" class="form-control" required maxlength="255"></div>
                                <div class="col-md-2"><label class="form-label">Sistema</label><input name="code_system" class="form-control" placeholder="CID/SNOMED/local" maxlength="30"></div>
                                <div class="col-md-2"><label class="form-label">Código</label><input name="code" class="form-control" maxlength="40"></div>
                                <div class="col-md-2"><label class="form-label">Início</label><input type="date" name="onset_date" class="form-control"></div>
                                <div class="col-12"><label class="form-label">Observações</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                            </div>
                            <button class="btn btn-primary mt-3"><i class="bi bi-plus-lg me-1"></i>Adicionar problema</button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#medications{{ $appointment->id }}">
                        <i class="bi bi-capsule me-2"></i>Medicamentos em uso
                        @if($medications->where('status','active')->count())
                            <span class="badge text-bg-info ms-2">{{ $medications->where('status','active')->count() }}</span>
                        @endif
                    </button>
                </h2>
                <div id="medications{{ $appointment->id }}" class="accordion-collapse collapse" data-bs-parent="#clinicalProfileAccordion{{ $appointment->id }}">
                    <div class="accordion-body">
                        <div class="table-responsive mb-3">
                            <table class="table align-middle">
                                <thead><tr><th>Medicamento</th><th>Via</th><th>Uso</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                @forelse($medications as $medication)
                                    <tr>
                                        <td><strong>{{ trim($medication->medication_name.' '.$medication->concentration) }}</strong></td>
                                        <td>{{ $medication->route ?: '—' }}</td>
                                        <td>{{ $medication->directions ?: '—' }}</td>
                                        <td>@if($medication->status==='active')<span class="badge text-bg-info">Em uso</span>@else<span class="badge text-bg-secondary">Suspenso</span>@endif</td>
                                        <td class="text-end">
                                            @if($medication->status==='active')
                                            @can('records.manage')
                                            <form method="POST" action="{{ route('clinical-profile.medications.stop',[$appointment,$medication]) }}" class="d-flex gap-2 justify-content-end">
                                                @csrf @method('PATCH')
                                                <input name="stop_reason" class="form-control form-control-sm" style="max-width:190px" placeholder="Motivo (opcional)">
                                                <button class="btn btn-sm btn-light border">Suspender</button>
                                            </form>
                                            @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><span class="text-secondary">Nenhum medicamento em uso registrado.</span></td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        @can('records.manage')
                        <form method="POST" action="{{ route('clinical-profile.medications.store',$appointment) }}" class="border rounded p-3">
                            @csrf
                            <strong class="d-block mb-3">Registrar medicamento em uso</strong>
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label">Medicamento *</label><input name="medication_name" class="form-control" required maxlength="180"></div>
                                <div class="col-md-2"><label class="form-label">Concentração</label><input name="concentration" class="form-control" maxlength="120"></div>
                                <div class="col-md-2"><label class="form-label">Via</label><input name="route" class="form-control" maxlength="120"></div>
                                <div class="col-md-2"><label class="form-label">Início</label><input type="date" name="started_on" class="form-control"></div>
                                <div class="col-md-12"><label class="form-label">Como usa / posologia informada</label><textarea name="directions" class="form-control" rows="2"></textarea></div>
                                <div class="col-md-12"><label class="form-label">Observações</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                            </div>
                            <button class="btn btn-primary mt-3"><i class="bi bi-plus-lg me-1"></i>Adicionar medicamento</button>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
