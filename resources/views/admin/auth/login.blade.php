
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | Banco Central</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #17263b;
            background:
                radial-gradient(ellipse at top left, #dceaff 0, transparent 45%),
                #f2f5fa;
        }

        .login-card {
            width: 100%;
            max-width: 430px;
            padding: 38px;
            border: 1px solid #e3e9f1;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 20px 65px #17315312;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 32px;
        }

        .brand-icon {
            display: grid;
            place-items: center;
            width: 48px;
            height: 48px;
            border-radius: 13px;
            background: #14243b;
            color: #fff;
            font-size: 25px;
            font-weight: 800;
        }

        .brand h1 { margin: 0; font-size: 17px; }
        .brand p { margin: 5px 0 0; color: #718096; font-size: 12px; }

        h2 { margin: 0 0 9px; font-size: 25px; }
        .subtitle { margin: 0 0 28px; color: #718096; font-size: 13px; line-height: 1.6; }

        .field { margin-bottom: 19px; }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #dce3ed;
            border-radius: 9px;
            outline: none;
            font: inherit;
            font-size: 13px;
        }

        input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px #2563eb16;
        }

        .submit {
            width: 100%;
            padding: 14px;
            border: 0;
            border-radius: 9px;
            background: #1d4ed8;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .submit:hover { background: #1e40af; }

        .error {
            margin: 0 0 20px;
            padding: 12px;
            border-radius: 8px;
            background: #fff0f0;
            color: #a51d1d;
            font-size: 12px;
        }

        .footer {
            margin-top: 24px;
            text-align: center;
            color: #8390a3;
            font-size: 11px;
            line-height: 1.6;
        }

        @media (max-width: 480px) {
            .login-card { padding: 26px 21px; }
        }
    </style>
</head>
<body>
    <main class="login-card">
        <div class="brand">
            <div class="brand-icon">B</div>
            <div>
                <h1>Banco Central</h1>
                <p>Portal de administración</p>
            </div>
        </div>

        <h2>Bienvenido</h2>
        <p class="subtitle">
            Inicia sesión con tu cuenta administrativa autorizada.
        </p>

        @if ($errors->any())
            <div class="error" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf

            <div class="field">
                <label for="email">Correo electrónico</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="admin@banco.com"
                    autocomplete="username"
                    maxlength="254"
                    required
                    autofocus
                >
            </div>

            <div class="field">
                <label for="password">Contraseña</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Introduce tu contraseña"
                    autocomplete="current-password"
                    maxlength="200"
                    required
                >
            </div>

            <button class="submit" type="submit">
                Iniciar sesión
            </button>
        </form>

        <div class="footer">
            Acceso exclusivo para administradores autorizados.<br>
            Banco Central · Sistema de gestión bancaria
        </div>
    </main>
</body>
</html>
