<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        /* Mesma identidade do painel (Filament): âmbar como primária, cinzas zinc, Inter. */
        :root {
            --primary-50: #fffbeb; --primary-400: #fbbf24; --primary-500: #f59e0b; --primary-600: #d97706; --primary-700: #b45309;
            --bg: #fafafa; --surface: #fff; --ink: #09090b; --ink-soft: #3f3f46; --muted: #71717a;
            --ring: rgb(9 9 11 / .05); --ring-input: rgb(9 9 11 / .1); --field: #fff; --field-disabled: #f4f4f5;
            --danger-bg: #fef2f2; --danger-ink: #b91c1c; --danger-ring: rgb(220 38 38 / .15);
            --success-bg: #f0fdf4; --success-ink: #15803d; --success-ring: rgb(22 163 74 / .15);
            --shadow: 0 1px 2px rgb(0 0 0 / .05);
            color-scheme: light;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                /* Faz o navegador desenhar listas de <select>, calendário e barras no tema escuro. */
                color-scheme: dark;
                --bg: #09090b; --surface: #18181b; --ink: #fafafa; --ink-soft: #d4d4d8; --muted: #a1a1aa;
                --ring: rgb(255 255 255 / .1); --ring-input: rgb(255 255 255 / .1); --field: rgb(255 255 255 / .05); --field-disabled: transparent;
                --danger-bg: rgb(220 38 38 / .1); --danger-ink: #f87171; --danger-ring: rgb(248 113 113 / .2);
                --success-bg: rgb(22 163 74 / .1); --success-ink: #4ade80; --success-ring: rgb(74 222 128 / .2);
                --primary-600: #f59e0b; --primary-500: #fbbf24;
            }
        }

        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; background: var(--bg); color: var(--ink); line-height: 1.5; font-size: .875rem; -webkit-font-smoothing: antialiased; }
        .wrap { max-width: 42rem; margin: 0 auto; padding: 0 1rem; }

        .topbar { background: var(--surface); box-shadow: var(--shadow); border-bottom: 1px solid var(--ring); margin-bottom: 2rem; }
        .topbar .wrap { display: flex; align-items: center; height: 4rem; }
        .brand { display: inline-flex; align-items: center; gap: .5rem; font-weight: 700; font-size: 1.25rem; letter-spacing: -.01em; color: var(--ink); text-decoration: none; }
        .brand-mark { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: .5rem; background: var(--primary-600); color: #fff; }

        .page-header { margin-bottom: 1.5rem; }
        h1 { font-size: 1.5rem; line-height: 2rem; font-weight: 700; letter-spacing: -.025em; margin: 0; }
        .page-header p { margin: .25rem 0 0; color: var(--muted); }

        /* Seção no estilo do Filament: cabeçalho + conteúdo, separados por uma linha */
        .section { background: var(--surface); border-radius: .75rem; box-shadow: var(--shadow); outline: 1px solid var(--ring); margin-bottom: 1.5rem; }
        .section-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--ring); }
        .section-header h2 { margin: 0; font-size: 1rem; font-weight: 600; }
        .section-header p { margin: .125rem 0 0; color: var(--muted); }
        .section-body { padding: 1.5rem; display: grid; gap: 1.5rem; }
        .grid-2 { display: grid; gap: 1.5rem; grid-template-columns: 1fr; }
        @media (min-width: 640px) { .grid-2 { grid-template-columns: 1fr 1fr; } }

        .field { display: grid; gap: .5rem; }
        label, .label { font-weight: 500; color: var(--ink); }
        .label sup { color: var(--danger-ink); font-weight: 500; }
        input, select { width: 100%; height: 2.25rem; padding: 0 .75rem; border: 0; border-radius: .5rem; font: inherit; color: var(--ink); background: var(--field); box-shadow: var(--shadow), inset 0 0 0 1px var(--ring-input); }
        select { appearance: none; padding-right: 2.25rem; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2371717a'%3e%3cpath fill-rule='evenodd' d='M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right .5rem center; background-size: 1.25rem; }
        /* A lista aberta não aceita fundo translúcido: sem cor sólida, vira texto claro em fundo branco. */
        select option { background-color: var(--surface); color: var(--ink); }
        input::placeholder { color: var(--muted); }
        input:focus, select:focus { outline: none; box-shadow: inset 0 0 0 2px var(--primary-600); }
        input:disabled, select:disabled { background-color: var(--field-disabled); color: var(--muted); cursor: not-allowed; }
        .hint { margin: 0; color: var(--muted); }

        .slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(5rem, 1fr)); gap: .5rem; }
        .slots:empty { display: none; }
        .slot { height: 2.25rem; border: 0; border-radius: .5rem; background: var(--field); color: var(--ink); font: inherit; font-weight: 500; cursor: pointer; box-shadow: var(--shadow), inset 0 0 0 1px var(--ring-input); }
        .slot:hover { box-shadow: inset 0 0 0 1px var(--primary-600); }
        .slot.selected { background: var(--primary-600); color: #fff; box-shadow: none; }
        .slot:focus-visible, .btn:focus-visible { outline: 2px solid var(--primary-600); outline-offset: 2px; }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: .375rem; width: 100%; min-height: 2.5rem; padding: .5rem 1rem; border: 0; border-radius: .5rem; background: var(--primary-600); color: #fff; font: inherit; font-weight: 600; cursor: pointer; text-decoration: none; box-shadow: var(--shadow); }
        .btn:hover { background: var(--primary-500); }
        .btn:disabled { opacity: .7; cursor: not-allowed; background: var(--primary-600); }
        .btn.secondary { background: var(--surface); color: var(--ink); box-shadow: var(--shadow), inset 0 0 0 1px var(--ring-input); }
        .btn.secondary:hover { background: var(--field-disabled); }
        .btn.outline-danger { background: var(--surface); color: var(--danger-ink); box-shadow: var(--shadow), inset 0 0 0 1px var(--danger-ring); }
        .btn.outline-danger:hover { background: var(--danger-bg); }
        .btn.outline-danger:focus-visible { outline-color: var(--danger-ink); }
        .actions { display: grid; gap: .75rem; }

        .alert { border-radius: .75rem; padding: 1rem; margin-bottom: 1.5rem; background: var(--danger-bg); color: var(--danger-ink); outline: 1px solid var(--danger-ring); }
        .alert ul { margin: 0; padding-left: 1.1rem; }
        .alert.success { background: var(--success-bg); color: var(--success-ink); outline-color: var(--success-ring); }
        .muted { color: var(--muted); margin: 0; }

        .badge { display: inline-flex; align-items: center; padding: .125rem .5rem; border-radius: .375rem; font-size: .75rem; font-weight: 500; outline: 1px solid; }
        .badge.warning { background: var(--primary-50); color: var(--primary-700); outline-color: rgb(217 119 6 / .2); }
        .badge.info { background: #eff6ff; color: #1d4ed8; outline-color: rgb(37 99 235 / .2); }
        .badge.success { background: var(--success-bg); color: var(--success-ink); outline-color: var(--success-ring); }
        .badge.danger { background: var(--danger-bg); color: var(--danger-ink); outline-color: var(--danger-ring); }
        .badge.gray { background: var(--field-disabled); color: var(--ink-soft); outline-color: var(--ring-input); }
        @media (prefers-color-scheme: dark) {
            .badge.warning { background: rgb(245 158 11 / .1); color: var(--primary-400); }
            .badge.info { background: rgb(59 130 246 / .1); color: #60a5fa; }
        }

        dl.summary { display: grid; grid-template-columns: auto 1fr; gap: .75rem 1.5rem; margin: 0; }
        dl.summary dt { color: var(--muted); }
        dl.summary dd { margin: 0; font-weight: 500; }

        footer { padding: 1rem 0 2.5rem; color: var(--muted); text-align: center; font-size: .75rem; }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="wrap">
            <a class="brand" href="{{ route('booking.create') }}">
                <span class="brand-mark" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                </span>
                {{ config('app.name') }}
            </a>
        </div>
    </header>

    <main class="wrap">
        @yield('content')
    </main>

    <footer>{{ config('app.name') }} · agendamento online</footer>

    @stack('scripts')
</body>
</html>
