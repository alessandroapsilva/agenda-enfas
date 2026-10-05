@php
    $activeAllergies = $allergies->where('status', 'active');
    $activeProblems = $problems->where('status', 'active');
    $activeMedications = $medications->where('status', 'active');
@endphp

<div class="card mt-3">
    <div class="card-header">
        <strong>Resumo clínico</strong>
    </div>
    <div class="card-body">
        @if($activeAllergies->isNotEmpty())
            <div class="alert alert-danger py-2 px-3 mb-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>{{ $activeAllergies->count() }} alergia(s) ativa(s)</strong>
                </div>
                @foreach($activeAllergies->take(4) as $allergy)
                    <div class="small">
                        {{ $allergy->substance }}
                        @if($allergy->severity !== 'unknown')
                            · {{ match($allergy->severity) {'mild'=>'leve','moderate'=>'moderada','severe'=>'grave',default=>$allergy->severity} }}
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="small text-secondary mb-3">
                <i class="bi bi-check-circle me-1"></i>Nenhuma alergia ativa registrada.
            </div>
        @endif

        <div class="row g-2">
            <div class="col-6">
                <div class="border rounded p-2 h-100">
                    <div class="small text-secondary">Problemas ativos</div>
                    <strong class="fs-5">{{ $activeProblems->count() }}</strong>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2 h-100">
                    <div class="small text-secondary">Medicamentos em uso</div>
                    <strong class="fs-5">{{ $activeMedications->count() }}</strong>
                </div>
            </div>
        </div>

        @if($record?->vitals)
            @php
                $weight = data_get($record->vitals, 'weight');
                $heightCm = data_get($record->vitals, 'height');
                $bmi = ($weight && $heightCm)
                    ? round($weight / (($heightCm / 100) ** 2), 1)
                    : null;
            @endphp
            @if($bmi)
                <div class="border-top mt-3 pt-3">
                    <div class="small text-secondary">IMC calculado</div>
                    <strong>{{ number_format($bmi,1,',','.') }}</strong>
                    <div class="small text-secondary">A partir do peso e altura deste atendimento.</div>
                </div>
            @endif
        @endif
    </div>
</div>
