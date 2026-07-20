<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sesión actualizada</title>
    {{-- Recupera automáticamente enviando al login con un token CSRF nuevo --}}
    <meta http-equiv="refresh" content="2;url={{ route('login') }}">
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, sans-serif; background: #f3f4f6;
               display: flex; min-height: 100vh; align-items: center; justify-content: center; }
        .card { max-width: 28rem; width: 100%; margin: 1.5rem; background: #fff; border: 1px solid #e5e7eb;
                border-radius: 0.75rem; box-shadow: 0 1px 2px rgba(0,0,0,.05); padding: 2rem; text-align: center; }
        h1 { font-size: 1.25rem; font-weight: 700; color: #1f2937; margin: 0 0 .5rem; }
        p { font-size: .875rem; color: #4b5563; margin: 0 0 1.5rem; }
        a.btn { display: inline-block; padding: .5rem 1rem; background: #2563eb; color: #fff;
                font-size: .875rem; font-weight: 500; border-radius: .5rem; text-decoration: none; }
        a.btn:hover { background: #1d4ed8; }
        @media (prefers-color-scheme: dark) {
            body { background: #111827; }
            .card { background: #1f2937; border-color: #374151; }
            h1 { color: #fff; } p { color: #9ca3af; }
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>La sesión cambió</h1>
        <p>Tu página estaba abierta desde antes de iniciar o cerrar sesión. Te redirigimos para continuar con una sesión actualizada.</p>
        <a class="btn" href="{{ route('login') }}">Continuar</a>
    </div>
</body>
</html>
