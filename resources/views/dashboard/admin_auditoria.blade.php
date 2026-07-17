@extends('layouts.app')

@section('title', 'Historial de Auditoría - Help Desk')

@section('content')
<div class="header-bar">
    <div class="page-title">
        <h1>Registro Histórico de Auditoría</h1>
        <p>Listado completo de todas las acciones y eventos automáticos o manuales registrados en el sistema.</p>
    </div>
    <div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver al Panel</span>
        </a>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    @if($auditorias->isEmpty())
        <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
            <i class="fa-solid fa-shield-halved" style="font-size: 3rem; margin-bottom: 1rem; color: rgba(255,255,255,0.05);"></i>
            <p>No se encontraron registros de auditoría en la base de datos.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="custom-table" style="font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Actor / Rol</th>
                        <th>Ticket</th>
                        <th>Acción</th>
                        <th>Descripción del Evento</th>
                        <th>Dirección IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($auditorias as $audit)
                        <tr>
                            <td style="color: var(--text-white); font-weight: 500;">
                                {{ \Carbon\Carbon::parse($audit->created_at)->format('d/m/Y H:i:s') }}
                            </td>
                            <td>
                                @if($audit->usuario_id)
                                    <strong>{{ $audit->actor_nombre }}</strong>
                                @else
                                    <span style="color: var(--primary); font-weight: 700;">
                                        <i class="fa-solid fa-robot" style="font-size: 0.8rem; margin-right: 0.25rem;"></i> SISTEMA
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($audit->numero_ticket)
                                    <a href="{{ route('tickets.show', $audit->ticket_id) }}" style="font-weight: 700;">
                                        {{ $audit->numero_ticket }}
                                    </a>
                                @else
                                    <span style="color: var(--text-muted); font-style: italic;">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge" style="
                                    font-size: 0.7rem; 
                                    padding: 0.15rem 0.5rem; 
                                    background: {{ 
                                        $audit->accion === 'creado' ? 'rgba(52, 211, 153, 0.1)' : (
                                        $audit->accion === 'estado_cambiado' ? 'rgba(96, 165, 250, 0.1)' : (
                                        $audit->accion === 'prioridad_cambiada' ? 'rgba(251, 146, 60, 0.1)' : (
                                        $audit->accion === 'auto_asignado' ? 'rgba(99, 102, 241, 0.1)' : 'rgba(156, 163, 175, 0.1)'
                                    ))) }}; 
                                    color: {{ 
                                        $audit->accion === 'creado' ? 'var(--clr-abierto)' : (
                                        $audit->accion === 'estado_cambiado' ? 'var(--clr-progreso)' : (
                                        $audit->accion === 'prioridad_cambiada' ? 'var(--clr-alta)' : (
                                        $audit->accion === 'auto_asignado' ? 'var(--primary)' : 'var(--text-muted)'
                                    ))) }};">
                                    {{ str_replace('_', ' ', $audit->accion) }}
                                </span>
                            </td>
                            <td style="color: var(--text-main); max-width: 400px; word-wrap: break-word; white-space: normal;">
                                {{ $audit->descripcion }}
                            </td>
                            <td style="font-family: monospace; color: var(--text-muted);">
                                {{ $audit->direccion_ip ?? 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($auditorias->hasPages())
            <div style="padding: 1.25rem 1.75rem; border-top: 1px solid var(--border-card); display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    Mostrando {{ $auditorias->firstItem() }} al {{ $auditorias->lastItem() }} de {{ $auditorias->total() }} registros de auditoría
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    @if($auditorias->onFirstPage())
                        <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed; padding: 0.4rem 0.8rem; font-size: 0.85rem;">Anterior</span>
                    @else
                        <a href="{{ $auditorias->previousPageUrl() }}" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Anterior</a>
                    @endif

                    @if($auditorias->hasMorePages())
                        <a href="{{ $auditorias->nextPageUrl() }}" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Siguiente</a>
                    @else
                        <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed; padding: 0.4rem 0.8rem; font-size: 0.85rem;">Siguiente</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
