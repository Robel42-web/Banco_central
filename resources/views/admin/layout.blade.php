
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel administrativo') | Banco Central</title>

    <style>
        :root {
            --navy: #10233f;
            --blue: #2457a7;
            --bg: #f3f6fb;
            --text: #1f2937;
            --muted: #64748b;
            --border: #e2e8f0;
            --white: #fff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 245px;
            padding: 25px 16px;
            background: var(--navy);
            color: white;
        }

        .brand {
            font-size: 20px;
            font-weight: bold;
            margin: 0 8px 8px;
        }

        .brand-subtitle {
            color: #b9c9e0;
            font-size: 12px;
            margin: 0 8px 30px;
        }

        .nav-link {
            display: block;
            padding: 12px;
            margin: 5px 0;
            border-radius: 8px;
            color: #dce7f5;
            text-decoration: none;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #28476f;
            color: white;
        }

        .sidebar form { margin-top: 28px; }

        .logout {
            width: 100%;
            padding: 11px;
            color: white;
            background: #344b69;
            border: 0;
            border-radius: 8px;
            cursor: pointer;
        }

        .main {
            margin-left: 245px;
            padding: 28px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
        }

        h1 { margin: 0 0 8px; font-size: 27px; }
        h2 { margin-top: 0; font-size: 19px; }
        p { line-height: 1.5; }
        .muted { color: var(--muted); }

        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 22px;
            box-shadow: 0 3px 12px rgb(15 23 42 / 4%);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-label { color: var(--muted); font-size: 14px; }
        .stat-value { font-size: 29px; font-weight: bold; margin-top: 12px; }

        .two-columns {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 22px;
        }

        .table-wrap { width: 100%; overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th, td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        th {
            color: var(--muted);
            background: #f8fafc;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            background: #e2e8f0;
            color: #334155;
            font-size: 12px;
        }

        .badge.active, .badge.completed, .badge.success {
            background: #dcfce7;
            color: #166534;
        }

        .badge.inactive, .badge.failed, .badge.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .search {
            width: 100%;
            max-width: 360px;
            padding: 11px 13px;
            margin: 12px 0 18px;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .button-link {
            display: inline-block;
            padding: 10px 14px;
            background: var(--blue);
            color: white;
            border-radius: 8px;
            text-decoration: none;
        }

        @media (max-width: 1000px) {
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .two-columns { grid-template-columns: 1fr; }
        }

        @media (max-width: 700px) {
            .sidebar {
                position: static;
                width: 100%;
            }

            .main { margin-left: 0; padding: 18px; }
            .topbar { align-items: flex-start; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }

        @stack('styles')
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="brand">Banco Central</div>
        <div class="brand-subtitle">Sistema de administración</div>

        <nav>
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               href="{{ route('admin.dashboard') }}">Dashboard</a>

            <a class="nav-link {{ request()->routeIs('admin.accounts') ? 'active' : '' }}"
               href="{{ route('admin.accounts') }}">Cuentas</a>

            <a class="nav-link {{ request()->routeIs('admin.branches') ? 'active' : '' }}"
               href="{{ route('admin.branches') }}">Sucursales</a>

            <a class="nav-link {{ request()->routeIs('admin.atms') ? 'active' : '' }}"
               href="{{ route('admin.atms') }}">Cajeros ATM</a>

            <a class="nav-link {{ request()->routeIs('admin.transactions') ? 'active' : '' }}"
               href="{{ route('admin.transactions') }}">Transacciones</a>
        </nav>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="logout" type="submit">Cerrar sesión</button>
        </form>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <h1>@yield('heading', 'Panel administrativo')</h1>
                <div class="muted">Gestión de la infraestructura bancaria</div>
            </div>
        </header>

        @if (session('success'))
            <div class="card">{{ session('success') }}</div>
        @endif

        @yield('content')
    </main>

    <script>
        document.querySelectorAll('[data-table-search]').forEach(function (input) {
            input.addEventListener('input', function () {
                const table = document.getElementById(input.dataset.tableSearch);
                if (!table) return;

                const query = input.value.toLowerCase();

                table.querySelectorAll('tbody tr').forEach(function (row) {
                    row.hidden = !row.textContent.toLowerCase().includes(query);
                });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
