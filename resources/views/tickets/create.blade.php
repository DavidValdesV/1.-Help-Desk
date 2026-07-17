@extends('layouts.app')

@section('title', 'Nuevo Ticket de Soporte - Help Desk')

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
    <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label for="titulo" class="form-label">Asunto / Título Resumido</label>
            <input type="text" name="titulo" id="titulo" class="form-control" placeholder="Ej: No conecta la VPN desde casa / Switch de red dañado" value="{{ old('titulo') }}" required>
            <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 0.25rem;">Proporciona un título descriptivo y conciso.</span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="categoria_id" class="form-label">Categoría de Incidencia</label>
                <select name="categoria_id" id="categoria_id" class="form-control" required>
                    <option value="" disabled selected>Selecciona una categoría</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}" {{ old('categoria_id') == $cat->id ? 'selected' : '' }}>{{ $cat->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="prioridad_id" class="form-label">Prioridad Sugerida</label>
                <select name="prioridad_id" id="prioridad_id" class="form-control" required>
                    <option value="" disabled>Selecciona la prioridad</option>
                    @foreach($prioridades as $prioridad)
                        <option value="{{ $prioridad->id }}" {{ (old('prioridad_id') ?? 2) == $prioridad->id ? 'selected' : '' }}>
                            {{ $prioridad->nombre }} (SLA: {{ $prioridad->horas_sla }} horas)
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="descripcion" class="form-label">Detalle del Problema (Descripción Completa)</label>
            <textarea name="descripcion" id="descripcion" rows="6" class="form-control" placeholder="Describe los síntomas, pasos que realizaste, equipos involucrados, etc. Esto ayudará a resolver el problema más rápido." required>{{ old('descripcion') }}</textarea>
        </div>

        <div class="form-group">
            <label for="adjuntos" class="form-label">Archivos Adjuntos (Capturas de pantalla, archivos de log...)</label>
            <input type="file" name="adjuntos[]" id="adjuntos" class="form-control" multiple style="padding: 0.65rem;">
            <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 0.25rem;">Puedes seleccionar múltiples archivos. Máximo 10MB por archivo.</span>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-card);">
            <a href="{{ route('cliente.dashboard') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Enviar Ticket de Soporte</span>
            </button>
        </div>
    </form>
</div>
@endsection
