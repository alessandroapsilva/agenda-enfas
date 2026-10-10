<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;background:#f5f7fb;padding:32px 16px;font-family:Arial,Helvetica,sans-serif;color:#263244;">
    <div style="max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #e6ebf2;border-radius:16px;padding:30px;box-shadow:0 8px 30px rgba(15,23,42,.05);">
        @if($logoUrl)
            <img
                src="{{ $logoUrl }}"
                alt="{{ $brandName }}"
                style="display:block;max-height:52px;max-width:180px;margin:0 0 22px;"
            >
        @endif

        <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#718096;margin-bottom:8px;">
            {{ $brandName }}
        </div>

        <div style="font-size:14px;line-height:1.65;color:#46556a;">
            {!! nl2br(e($messageBody)) !!}
        </div>

        <div style="margin-top:26px;padding-top:18px;border-top:1px solid #edf1f5;font-size:11px;color:#8793a4;">
            Mensagem enviada pelo ENFAS Agenda.
        </div>
    </div>
</body>
</html>
