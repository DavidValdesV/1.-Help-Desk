@extends('layouts.app')

@section('title', 'Nuevo Ticket de Soporte - Help Desk')
@section('suppress_global_errors', '1')

@section('content')
<div class="header-bar">
    <div class="page-title">
        <h1>Reportar Nueva Incidencia</h1>
        <p>Completa el formulario para abrir un ticket. El sistema lo asignará de forma automática al personal disponible.</p>
    </div>
    <div>
        <a href="{{ route('cliente.dashboard') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver</span>
        </a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" data-loading-form>
        @csrf

        <div class="form-group">
            <label for="titulo" class="form-label">Asunto / Título Resumido</label>
            <input type="text" name="titulo" id="titulo" class="form-control @error('titulo') is-invalid @enderror" placeholder="Ej: No conecta la VPN desde casa / Switch de red dañado" value="{{ old('titulo') }}" minlength="8" maxlength="255" required>
            <div class="field-feedback-row">
                @error('titulo')
                    <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                @else
                    <span style="color: var(--text-muted);">Proporciona un título descriptivo y conciso.</span>
                @enderror
                <span id="titulo-counter" class="char-counter" data-min="8">0 / 8 mín.</span>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="categoria_id" class="form-label">Categoría de Incidencia</label>
                <select name="categoria_id" id="categoria_id" class="form-control @error('categoria_id') is-invalid @enderror" required>
                    <option value="" disabled selected>Selecciona una categoría</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}" {{ old('categoria_id') == $cat->id ? 'selected' : '' }}>{{ $cat->nombre }}</option>
                    @endforeach
                </select>
                @error('categoria_id')
                    <div class="field-feedback-row"><span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span></div>
                @enderror
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="prioridad_id" class="form-label">Prioridad Sugerida</label>
                <select name="prioridad_id" id="prioridad_id" class="form-control @error('prioridad_id') is-invalid @enderror" required>
                    <option value="" disabled>Selecciona la prioridad</option>
                    @foreach($prioridades as $prioridad)
                        <option value="{{ $prioridad->id }}" {{ (old('prioridad_id') ?? 2) == $prioridad->id ? 'selected' : '' }}>
                            {{ $prioridad->nombre }} (SLA: {{ $prioridad->horas_sla }} horas)
                        </option>
                    @endforeach
                </select>
                @error('prioridad_id')
                    <div class="field-feedback-row"><span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span></div>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="descripcion" class="form-label">Detalle del Problema (Descripción Completa)</label>
            <textarea name="descripcion" id="descripcion" rows="6" class="form-control @error('descripcion') is-invalid @enderror" placeholder="Describe los síntomas, pasos que realizaste, equipos involucrados, etc. Esto ayudará a resolver el problema más rápido." minlength="15" required>{{ old('descripcion') }}</textarea>
            <div class="field-feedback-row">
                @error('descripcion')
                    <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                @else
                    <span style="color: var(--text-muted);">Detalla el problema con al menos 15 caracteres.</span>
                @enderror
                <span id="descripcion-counter" class="char-counter" data-min="15">0 / 15 mín.</span>
            </div>
        </div>

        <div class="form-group">
            <label for="adjuntos" class="form-label">Archivos Adjuntos (Capturas de pantalla, archivos de log...)</label>
            <input type="file" name="adjuntos[]" id="adjuntos" class="form-control @error('adjuntos.*') is-invalid @enderror" multiple style="padding: 0.65rem;">
            <div class="field-feedback-row">
                @error('adjuntos.*')
                    <span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</span>
                @else
                    <span style="color: var(--text-muted);">Puedes seleccionar múltiples archivos. Máximo 10MB por archivo.</span>
                @enderror
            </div>
        </div>

        <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-card);">
            <a href="{{ route('cliente.dashboard') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary" data-loading-text="Enviando ticket...">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Enviar Ticket a Soporte</span>
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        function vincularContador(inputId, counterId) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);
            if (!input || !counter) return;

            const min = parseInt(counter.dataset.min, 10) || 0;

            function actualizar() {
                const len = input.value.trim().length;
                counter.classList.remove('is-pending', 'is-valid');

                if (len === 0) {
                    counter.textContent = `0 / ${min} mín.`;
                } else if (len < min) {
                    const faltan = min - len;
                    counter.innerHTML = `<i class="fa-solid fa-pen"></i> ${len} / ${min} (faltan ${faltan})`;
                    counter.classList.add('is-pending');
                } else {
                    counter.innerHTML = `<i class="fa-solid fa-check"></i> ${len} caracteres`;
                    counter.classList.add('is-valid');
                    input.classList.remove('is-invalid');
                }
            }

            input.addEventListener('input', actualizar);
            actualizar();
        }

        vincularContador('titulo', 'titulo-counter');
        vincularContador('descripcion', 'descripcion-counter');
    })();
</script>
@endsection
