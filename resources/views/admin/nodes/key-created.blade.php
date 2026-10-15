@extends('admin.layout')

@section('title', 'API Key generada')
@section('heading', 'Nodo registrado correctamente')

@section('content')
<div class="card">
    <h2>{{ $nodeType }}: {{ $nodeName }}</h2>

    <p>
        Código: <strong>{{ $nodeCode }}</strong>
    </p>

    <p>
        La siguiente API Key se muestra ahora porque acaba de generarse.
        Cópiala y guárdala en un lugar seguro.
    </p>

    <div style="padding:15px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;color:#9a3412;margin:20px 0">
        <strong>Advertencia:</strong> si abandonas esta página,
        no podrás recuperar la clave original desde la base de datos.
        Si la pierdes, tendrás que generar otra.
    </div>

    <label for="generated-key"><strong>API Key completa</strong></label>

    <textarea id="generated-key" readonly rows="4"
        autocomplete="off" spellcheck="false"
        style="width:100%;padding:12px;margin-top:8px;overflow-wrap:anywhere">{{ $apiKey }}</textarea>

    <button type="button" onclick="copyApiKey()">
        Copiar API Key
    </button>

    <p id="copy-message" role="status" aria-live="polite"></p>

    <a class="button-link" href="{{ route($returnRoute) }}">
        Volver al listado
    </a>
</div>

<script>
async function copyApiKey() {
    const field = document.getElementById('generated-key');
    const message = document.getElementById('copy-message');

    try {
        await navigator.clipboard.writeText(field.value);
        message.textContent = 'API Key copiada correctamente.';
    } catch (error) {
        field.focus();
        field.select();
        message.textContent =
            'No se pudo copiar automáticamente. Copia el texto seleccionado.';
    }
}
</script>
@endsection
