<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Agendly')</title>
    <style>
        :root {
            --bg: #f6f7f9; --card: #fff; --ink: #1c1f24; --muted: #6b7280;
            --brand: #2563eb; --brand-ink: #fff; --line: #e5e7eb;
            --danger: #b91c1c; --danger-bg: #fef2f2;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: var(--bg); color: var(--ink); line-height: 1.5; }
        .wrap { max-width: 640px; margin: 0 auto; padding: 0 1rem; }
        .topbar { background: var(--card); border-bottom: 1px solid var(--line); padding: .9rem 0; margin-bottom: 1.5rem; }
        .brand { font-weight: 700; font-size: 1.15rem; color: var(--ink); text-decoration: none; }
        h1 { font-size: 1.5rem; margin: 0 0 1rem; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; }
        label { display: block; font-weight: 600; margin: 1rem 0 .35rem; }
        label:first-of-type { margin-top: 0; }
        input, select { width: 100%; padding: .65rem .75rem; border: 1px solid var(--line); border-radius: 8px; font: inherit; background: #fff; color: var(--ink); }
        input:focus, select:focus, .slot:focus-visible, .btn:focus-visible { outline: 2px solid var(--brand); outline-offset: 1px; }
        .slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: .5rem; margin-top: .5rem; }
        .slot { padding: .55rem; border: 1px solid var(--line); border-radius: 8px; background: #fff; font: inherit; cursor: pointer; }
        .slot:hover { border-color: var(--brand); }
        .slot.selected { background: var(--brand); color: var(--brand-ink); border-color: var(--brand); }
        .btn { display: block; width: 100%; margin-top: 1.5rem; padding: .8rem; border: 0; border-radius: 8px; background: var(--brand); color: var(--brand-ink); font: inherit; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .muted { color: var(--muted); margin: .5rem 0 0; }
        .alert { background: var(--danger-bg); color: var(--danger); border: 1px solid #fecaca; border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1rem; }
        .alert ul { margin: 0; padding-left: 1.1rem; }
        .alert.success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .btn.outline-danger { background: transparent; color: var(--danger); border: 1px solid #fecaca; }
        .btn.outline-danger:hover { background: var(--danger-bg); }
        .btn.outline-danger:focus-visible { outline-color: var(--danger); }
        dl.summary { display: grid; grid-template-columns: auto 1fr; gap: .5rem 1rem; margin: 0; }
        dl.summary dt { color: var(--muted); }
        dl.summary dd { margin: 0; font-weight: 600; }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="wrap"><a class="brand" href="{{ route('booking.create') }}">Agendly</a></div>
    </header>

    <main class="wrap">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
