@extends('layouts.app')

@section('title', 'Mesa de Soporte - Panel Técnico')

@section('content')
<div class="header-bar">
    <div class="page-title">
        <h1>Bandeja de Tickets Asignados</h1>
        <p>Gestiona, soluciona y comenta los requerimientos técnicos de los clientes asignados.</p>
    </div>
</div>

<!-- Metrics row for workload -->
<div class="metrics-grid">
    <div class="metric-card metric-total">
        <div class="metric-icon">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <div class="metric-info">
            <span class="metric-value">{{ $metricas->activos ?? 0 }}</span>
            <span class="metric-label">Asignados Activos</span>
        </div>
    </div>
    <div class="metric-card metric-progress">
        <div class="metric-icon">
            <i class="fa-solid fa-spinner"></i>
        </div>
        <div class="metric-info">
            <span class="metric-value">{{ $metricas->en_progreso ?? 0 }}</span>
            <span class="metric-label">En Progreso</span>
        </div>
    </div>
    <div class="metric-card metric-resolved">
        <div class="metric-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="metric-info">
            <span class="metric-value">{{ $metricas->resueltos ?? 0 }}</span>
            <span class="metric-label">Resueltos</span>
        </div>
    </div>
    <div class="metric-card metric-vencido">
        <div class="metric-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="metric-info">
            <span class="metric-value" style="color: {{ ($metricas->vencidos ?? 0) > 0 ? 'var(--clr-critica)' : 'var(--text-white)' }};">
                {{ $metricas->vencidos ?? 0 }}
            </span>
            <span class="metric-label">Vencidos</span>
        </div>
    </div>
</div>

<!-- Filters Form -->
<form method="GET" action="{{ route('tecnico.dashboard') }}" class="card filters-form">
    <div class="filter-item">
        <label for="buscar" class="form-label">Buscar en mis tickets</label>
        <input type="text" name="buscar" id="buscar" class="form-control" placeholder="Título, descripción, código..." value="{{ request('buscar') }}">
    </div>
    <div class="filter-item">
        <label for="estado" class="form-label">Estado</label>
        <select name="estado" id="estado" class="form-control">
            <option value="">Todos los estados</option>
            <option value="abierto" {{ request('estado') === 'abierto' ? 'selected' : '' }}>Abierto</option>
            <option value="en_progreso" {{ request('estado') === 'en_progreso' ? 'selected' : '' }}>En Progreso</option>
            <option value="en_espera" {{ request('estado') === 'en_espera' ? 'selected' : '' }}>En Espera</option>
            <option value="resuelto" {{ request('estado') === 'resuelto' ? 'selected' : '' }}>Resuelto</option>
            <option value="cerrado" {{ request('estado') === 'cerrado' ? 'selected' : '' }}>Cerrado</option>
        </select>
    </div>
    <div class="filter-item">
        <label for="prioridad_id" class="form-label">Prioridad</label>
        <select name="prioridad_id" id="prioridad_id" class="form-control">
            <option value="">Todas las prioridades</option>
            @foreach($prioridades as $prioridad)
                <option value="{{ $prioridad->id }}" {{ request('prioridad_id') == $prioridad->id ? 'selected' : '' }}>{{ $prioridad->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-item" style="flex-grow: 0; min-width: auto; display: flex; gap: 0.5rem;">
        <button type="submit" class="btn btn-primary" style="height: 44px; padding: 0 1.25rem;">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
        @if(request()->anyFilled(['buscar', 'estado', 'prioridad_id']))
            <a href="{{ route('tecnico.dashboard') }}" class="btn btn-secondary" style="height: 44px; padding: 0 1.25rem; display: flex; align-items: center;">
                <i class="fa-solid fa-xmark"></i>
            </a>
        @endif
    </div>
</form>

<!-- Tickets List Table -->
<div class="card" style="padding: 0; overflow: hidden;">
    @if($tickets->isEmpty())
        <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
            <i class="fa-solid fa-clipboard-list" style="font-size: 3rem; margin-bottom: 1rem; color: rgba(255,255,255,0.05);"></i>
            <p>No tienes ningún ticket asignado en tu bandeja.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Asunto / Detalle</th>
                        <th>Prioridad / SLA</th>
                        <th>Estado</th>
                        <th>Fecha de Ingreso</th>
                        <th>Límite SLA</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                        <tr style="{{ $ticket->estaVencido() ? 'border-left: 3px solid var(--clr-critica);' : '' }}">
                            <td style="font-weight: 700; color: var(--primary);">{{ $ticket->numero_ticket }}</td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-white);">{{ $ticket->cliente->nombre }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $ticket->cliente->departamento }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-white);">{{ $ticket->titulo }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $ticket->descripcion }}
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-prioridad-{{ strtolower($ticket->prioridad->nombre ?? 'baja') }}">
                                    {{ $ticket->prioridad->nombre ?? 'Baja' }}
                                </span>
                                <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.15rem;">
                                    SLA: {{ $ticket->prioridad->horas_sla }} hrs
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-estado-{{ $ticket->estado }}">
                                    {{ str_replace('_', ' ', $ticket->estado) }}
                                </span>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td style="font-size: 0.85rem; font-weight: 600; color: {{ $ticket->estaVencido() ? 'var(--clr-critica)' : 'var(--text-muted)' }}">
                                {{ $ticket->due_at ? $ticket->due_at->format('d/m/Y H:i') : 'N/A' }}
                                @if($ticket->estaVencido())
                                    <span style="font-size: 0.7rem; display: block; color: var(--clr-critica); font-weight: 700; text-transform: uppercase;">¡VENCIDO!</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; border-radius: var(--radius-sm);">
                                    <i class="fa-solid fa-screwdriver-wrench"></i>
                                    <span>Resolver</span>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($tickets->hasPages())
            <div style="padding: 1rem 1.75rem; border-top: 1px solid var(--border-card); display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    Mostrando {{ $tickets->firstItem() }} al {{ $tickets->lastItem() }} de {{ $tickets->total() }} tickets asignados
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    @if($tickets->onFirstPage())
                        <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed; padding: 0.4rem 0.8rem; font-size: 0.85rem;">Anterior</span>
                    @else
                        <a href="{{ $tickets->previousPageUrl() }}" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Anterior</a>
                    @endif

                    @if($tickets->hasMorePages())
                        <a href="{{ $tickets->nextPageUrl() }}" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Siguiente</a>
                    @else
                        <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed; padding: 0.4rem 0.8rem; font-size: 0.85rem;">Siguiente</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
