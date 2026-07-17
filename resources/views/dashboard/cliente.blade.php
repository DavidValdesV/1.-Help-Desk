@extends('layouts.app')

@section('title', 'Mesa de Ayuda - Mis Tickets')

@section('content')
<div class="header-bar">
    <div class="page-title">
        <h1>Mis Tickets de Soporte</h1>
        <p>Monitorea y reporta problemas técnicos de red, hardware o software.</p>
    </div>
    <div>
        <a href="{{ route('tickets.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-circle-plus"></i>
            <span>Nuevo Ticket</span>
        </a>
    </div>
</div>

<!-- Filters Form -->
<form method="GET" action="{{ route('cliente.dashboard') }}" class="card filters-form">
    <div class="filter-item">
        <label for="buscar" class="form-label">Buscar</label>
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
        <label for="categoria_id" class="form-label">Categoría</label>
        <select name="categoria_id" id="categoria_id" class="form-control">
            <option value="">Todas las categorías</option>
            @foreach($categorias as $cat)
                <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>{{ $cat->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-item" style="flex-grow: 0; min-width: auto; display: flex; gap: 0.5rem;">
        <button type="submit" class="btn btn-primary" style="height: 44px; padding: 0 1.25rem;">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
        @if(request()->anyFilled(['buscar', 'estado', 'categoria_id']))
            <a href="{{ route('cliente.dashboard') }}" class="btn btn-secondary" style="height: 44px; padding: 0 1.25rem; display: flex; align-items: center;">
                <i class="fa-solid fa-xmark"></i>
            </a>
        @endif
    </div>
</form>

<!-- Tickets List Table -->
<div class="card" style="padding: 0; overflow: hidden;">
    @if($tickets->isEmpty())
        <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
            <i class="fa-solid fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem; color: rgba(255,255,255,0.05);"></i>
            <p>No tienes ningún ticket registrado que coincida con la búsqueda.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Asignado a</th>
                        <th>Fecha de Reporte</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);">{{ $ticket->numero_ticket }}</td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-white);">{{ $ticket->titulo }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $ticket->descripcion }}
                                </div>
                            </td>
                            <td>
                                <i class="fa-solid {{ $ticket->categoria->icono ?? 'fa-circle-question' }}" style="margin-right: 0.5rem; opacity: 0.7;"></i>
                                {{ $ticket->categoria->nombre }}
                            </td>
                            <td>
                                <span class="badge badge-prioridad-{{ strtolower($ticket->prioridad->nombre ?? 'baja') }}">
                                    {{ $ticket->prioridad->nombre ?? 'Baja' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-estado-{{ $ticket->estado }}">
                                    {{ str_replace('_', ' ', $ticket->estado) }}
                                </span>
                            </td>
                            <td style="font-size: 0.9rem; color: var(--text-main);">
                                @if($ticket->tecnico)
                                    <i class="fa-regular fa-user" style="margin-right: 0.35rem; font-size: 0.8rem;"></i>
                                    {{ $ticket->tecnico->nombre }}
                                @else
                                    <span style="color: var(--text-muted); font-style: italic;">Auto-asignando...</span>
                                @endif
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; border-radius: var(--radius-sm);">
                                    <i class="fa-solid fa-eye"></i>
                                    <span>Ver</span>
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
                    Mostrando {{ $tickets->firstItem() }} al {{ $tickets->lastItem() }} de {{ $tickets->total() }} tickets
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
