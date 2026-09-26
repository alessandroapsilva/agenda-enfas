<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('code') · ENFAS Agenda</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f7fb;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#172033}
        .box{max-width:620px;margin:24px;padding:42px;border:1px solid #e3e8f0;border-radius:24px;background:#fff;box-shadow:0 24px 60px rgba(20,28,45,.08);text-align:center}
        .code{font-size:5rem;font-weight:800;letter-spacing:-.08em;line-height:1;color:#335eea}
        h1{margin:18px 0 8px;font-size:1.65rem}
        p{margin:0 auto 24px;color:#657087;line-height:1.6}
        a{display:inline-block;text-decoration:none;padding:12px 18px;border-radius:12px;background:#335eea;color:#fff;font-weight:700}
    </style>
</head>
<body>
    <div class="box">
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="/inicio">Voltar para a Central</a>
    </div>
</body>
</html>
