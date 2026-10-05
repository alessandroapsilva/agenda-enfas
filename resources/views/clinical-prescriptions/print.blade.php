<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>{{ $prescription->title }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{font-family:Arial,Helvetica,sans-serif;color:#111827;margin:0;background:#f3f4f6}
.sheet{width:210mm;min-height:297mm;margin:0 auto;background:#fff;padding:20mm;box-sizing:border-box}
.header{border-bottom:2px solid #111827;padding-bottom:14px;margin-bottom:24px}
.brand{font-size:22px;font-weight:700}.muted{color:#6b7280;font-size:12px}
.context{display:grid;grid-template-columns:1fr 1fr;gap:10px 30px;margin-bottom:26px}
.label{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#6b7280}
.value{font-size:14px;font-weight:600;margin-top:3px}
h1{font-size:20px;margin:0 0 22px}.item{padding:0 0 18px;margin-bottom:18px;border-bottom:1px solid #e5e7eb}
.item-title{font-size:16px;font-weight:700}.item-row{margin-top:7px;font-size:13px;white-space:pre-wrap}
.notes{margin-top:20px;padding:14px;border:1px solid #e5e7eb;border-radius:8px;white-space:pre-wrap}
.signature{margin-top:46px;padding-top:18px;border-top:1px solid #d1d5db}
.hash{font-family:monospace;font-size:10px;word-break:break-all;color:#4b5563}
.actions{position:fixed;right:20px;top:20px}
@media print{body{background:#fff}.sheet{margin:0;width:auto;min-height:auto;padding:12mm}.actions{display:none}}
</style>
</head>
<body>
<div class="actions"><button onclick="window.print()">Imprimir</button></div>
<div class="sheet">
    <div class="header">
        <div class="brand">Enfermagem Alessandro Silva</div>
        <div class="muted">Documento clínico emitido pelo ENFAS Agenda</div>
    </div>

    <h1>{{ $prescription->title }}</h1>

    <div class="context">
        <div><div class="label">Paciente</div><div class="value">{{ $prescription->patient->displayName() }}</div></div>
        <div><div class="label">RGEA</div><div class="value">{{ $prescription->patient->rgea_number ?: '—' }}</div></div>
        <div><div class="label">Profissional</div><div class="value">{{ $prescription->professional->name }}</div></div>
        <div>
            <div class="label">Registro profissional</div>
            <div class="value">{{ trim(implode(' ',array_filter([$prescription->professional->council_type,$prescription->professional->council_number,$prescription->professional->council_state]))) ?: '—' }}</div>
        </div>
        @if($prescription->appointment)
        <div><div class="label">Atendimento</div><div class="value">{{ $prescription->appointment->code }}</div></div>
        <div><div class="label">Data</div><div class="value">{{ $prescription->appointment->start_at->format('d/m/Y H:i') }}</div></div>
        @endif
    </div>

    @foreach($prescription->items as $item)
    <div class="item">
        <div class="item-title">{{ $loop->iteration }}. {{ trim(implode(' ',array_filter([$item->medication_name,$item->concentration,$item->dosage_form]))) }}</div>
        @if($item->quantity)<div class="item-row"><strong>Quantidade:</strong> {{ $item->quantity }}</div>@endif
        @if($item->route)<div class="item-row"><strong>Via:</strong> {{ $item->route }}</div>@endif
        <div class="item-row"><strong>Posologia:</strong> {{ $item->directions }}</div>
        @if($item->duration)<div class="item-row"><strong>Duração:</strong> {{ $item->duration }}</div>@endif
        @if($item->notes)<div class="item-row"><strong>Observações:</strong> {{ $item->notes }}</div>@endif
    </div>
    @endforeach

    @if($prescription->notes)
    <div class="notes"><strong>Orientações gerais</strong><br>{{ $prescription->notes }}</div>
    @endif

    <div class="signature">
        @if($prescription->status === 'signed' && $prescription->document?->signatures?->isNotEmpty())
            @php($signature = $prescription->document->signatures->sortByDesc('signed_at')->first())
            <strong>Assinado eletronicamente por {{ $signature->signer_name }}</strong>
            <div class="muted">{{ $signature->signer_registry }} · {{ $signature->signed_at?->format('d/m/Y H:i') }}</div>
            <div class="muted" style="margin-top:10px">Integridade SHA-256</div>
            <div class="hash">{{ $signature->document_hash }}</div>
        @else
            <strong>RASCUNHO — NÃO EMITIDO</strong>
            <div class="muted">Este documento ainda não possui assinatura eletrônica.</div>
        @endif
    </div>
</div>
</body>
</html>
