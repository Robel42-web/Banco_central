
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Panel administrativo') | Banco Central</title>

    <style>
        :root {
            --navy: #10233f;
            --navy-light: #1b3558;
            --blue: #2457a7;
            --background: #f3f6fb;
            --text: #1f2937;
            --muted: #64748b;
            --border: #e2e8f0;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--background);
            color: var(--text);
        }

        /* Barra lateral */

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 250px;
            padding: 26px 17px;
            background: var(--navy);
            color: var(--white);
            overflow-y: auto;
        }

        .brand {
            margin: 0 8px 8px;
            font-size: 21px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .brand-subtitle {
            margin: 0 8px 32px;
            color: #b9c9e0;
            font-size: 12px;
        }

        .nav-section {
            margin: 20px 8px 10px;
            color: #94a9c5;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 12px;
            margin: 5px 0;
            border-radius: 8px;
            color: #dce7f5;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s, color 0.2s;
        }

        .nav-link:hover {
            background: var(--navy-light);
            color: var(--white);
        }

        .nav-link.active {
            background: var(--blue);
            color: var(--white);
            font-weight: bold;
        }

        .sidebar-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #344b69;
        }

        .logout {
            width: 100%;
            padding: 12px;
            border: 1px solid #526985;
            border-radius: 8px;
            background: #263e5d;
            color: var(--white);
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }

        .logout:hover {
            background: #3b5270;
        }

        /* Contenido principal */

        .main {
            min-height: 100vh;
            margin-left: 250px;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .page-heading {
            margin: 0 0 8px;
            font-size: 28px;
            font-weight: 700;
            color: #172b4d;
        }

        .page-description {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        h2 {
            margin-top: 0;
            font-size: 19px;
        }

        p {
            line-height: 1.6;
        }

        .muted {
            color: var(--muted);
        }

        /* Tarjetas */

        .card {
            margin-bottom: 24px;
            padding: 23px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            box-shadow: 0 3px 12px rgb(15 23 42 / 4%);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            padding: 22px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            box-shadow: 0 3px 12px rgb(15 23 42 / 4%);
        }

        .stat-label {
            color: var(--muted);
            font-size: 13px;
        }

        .stat-value {
            margin-top: 12px;
            font-size: 30px;
            font-weight: bold;
            color: #172b4d;
            overflow-wrap: anywhere;
        }

        .two-columns {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(250px, 1fr);
            gap: 22px;
        }

        /* Tablas */

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        th {
            background: #f8fafc;
            color: var(--muted);
            font-weight: 600;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Estados */

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #e2e8f0;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge.active,
        .badge.completed,
        .badge.success,
        .badge.completado,
        .badge.activo {
            background: #dcfce7;
            color: #166534;
        }

        .badge.inactive,
        .badge.failed,
        .badge.error,
        .badge.rechazado,
        .badge.inactivo {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.pending,
        .badge.pending_review,
        .badge.pendiente {
            background: #fef3c7;
            color: #92400e;
        }

        /* Búsqueda y enlaces */

        .search {
            width: 100%;
            max-width: 380px;
            padding: 12px 14px;
            margin: 12px 0 18px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: var(--white);
            color: var(--text);
            font-size: 14px;
        }

        .search:focus {
            outline: 2px solid #93c5fd;
            border-color: var(--blue);
        }

        .button-link {
            display: inline-block;
            padding: 11px 15px;
            border-radius: 8px;
            background: var(--blue);
            color: var(--white);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .button-link:hover {
            background: #1d4688;
        }

        .alert {
            margin-bottom: 20px;
            padding: 14px 17px;
            border-radius: 8px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty-state {
            padding: 25px;
            text-align: center;
            color: var(--muted);
        }

        /* Adaptación a móviles */

        @media (max-width: 1100px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .two-columns {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                padding: 20px 16px;
            }

            .brand-subtitle {
                margin-bottom: 20px;
            }

            .main {
                margin-left: 0;
                padding: 20px 14px;
            }

            .topbar {
                align-items: flex-start;
            }

            .page-heading {
                font-size: 23px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-value {
                font-size: 24px;
            }

            .card {
                padding: 16px;
            }
        }

        @stack('styles')
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="brand">Banco Central</div>
        <div class="brand-subtitle">Sistema de administración</div>

        <div class="nav-section">Principal</div>

        <nav>
            <a
                class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                href="{{ route('admin.dashboard') }}"
            >
                <span>▦</span>
                <span>Dashboard</span>
            </a>

            <div class="nav-section">Gestión bancaria</div>

            <a
                class="nav-link {{ request()->routeIs('admin.accounts') ? 'active' : '' }}"
                href="{{ route('admin.accounts') }}"
            >
                <span>▤</span>
                <span>Cuentas</span>
            </a>

            <a
                class="nav-link {{ request()->routeIs('admin.branches') ? 'active' : '' }}"
                href="{{ route('admin.branches') }}"
            >
                <span>⌂</span>
                <span>Sucursales</span>
            </a>

            <a
                class="nav-link {{ request()->routeIs('admin.atms') ? 'active' : '' }}"
                href="{{ route('admin.atms') }}"
            >
                <span>▣</span>
                <span>Cajeros ATM</span>
            </a>

            <a
                class="nav-link {{ request()->routeIs('admin.transactions') ? 'active' : '' }}"
                href="{{ route('admin.transactions') }}"
            >
                <span>⇄</span>
                <span>Transacciones</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="logout" type="submit">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div>
                <h1 class="page-heading">
                    @yield('heading', 'Panel administrativo')
                </h1>

                <p class="page-description">
                    Gestión de la infraestructura bancaria
                </p>
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <script>
        document.querySelectorAll('[data-table-search]').forEach(function (input) {
            input.addEventListener('input', function () {
                const table = document.getElementById(input.dataset.tableSearch);

                if (!table) {
                    return;
                }

                const query = input.value.toLowerCase().trim();

                table.querySelectorAll('tbody tr').forEach(function (row) {
                    row.hidden = !row.textContent.toLowerCase().includes(query);
                });
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
