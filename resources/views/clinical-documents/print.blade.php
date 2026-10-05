<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>{{ $document->title }}</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{font-family:Arial,Helvetica,sans-serif;color:#111827;margin:0;background:#fff}
.page{width:190mm;min-height:267mm;margin:0 auto;padding:14mm;box-sizing:border-box}
.header{border-bottom:2px solid #111827;padding-bottom:10px;margin-bottom:24px}
.header h1{font-size:18px;margin:0 0 6px}.header p{font-size:12px;margin:2px 0;color:#4b5563}
.content{white-space:pre-wrap;font-size:13px;line-height:1.65;min-height:150mm}
.signature{margin-top:36px;padding-top:18px;border-top:1px solid #d1d5db;font-size:11px}
.signature strong,.signature span{display:block}.hash{font-family:monospace;font-size:9px;word-break:break-all;color:#6b7280;margin-top:8px}
.actions{position:fixed;right:16px;top:16px}@media print{.actions{display:none}.page{width:auto;min-height:auto;margin:0;padding:12mm}}
</style>
</head>
<body>
<button class="actions" onclick="window.print()">Imprimir</button>
<div class="page">
    <div class="header">
        <h1>{{ $document->title }}</h1>
        <p><strong>Paciente:</strong> {{ $document->patient->displayName() }} · RGEA {{ $document->patient->rgea_number }}</p>
        @if($document->professional)<p><strong>Profissional:</strong> {{ $document->professional->name }}</p>@endif
        @if($document->appointment)<p><strong>Atendimento:</strong> {{ $document->appointment->code }} · {{ $document->appointment->start_at?->format('d/m/Y H:i') }}</p>@endif
    </div>

    <div class="content">{{ $document->content }}</div>

    @if($document->signatures->isNotEmpty())
    <div class="signature">
        @foreach($document->signatures as $signature)
            <strong>Assinado eletronicamente por {{ $signature->signer_name }}</strong>
            @if($signature->signer_registry)<span>{{ $signature->signer_registry }}</span>@endif
            <span>{{ $signature->signed_at?->format('d/m/Y H:i') }}</span>
            <div class="hash">Integridade: {{ $signature->document_hash }}</div>
        @endforeach
    </div>
    @endif
</div>
</body>
</html>
