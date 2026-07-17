@extends('layouts.app')

@section('title', 'Panel de Administrador - Help Desk')

@section('content')
<div class="header-bar">
    <div class="page-title">
        <h1>Panel de Administración General</h1>
        <p>Estadísticas del sistema, carga de técnicos y registro de auditoría en tiempo real.</p>
    </div>
</div>

<!-- KPIs Grid -->
<div class="metrics-grid">
    <div class="metric-card metric-total">
        <div class="metric-icon">
            <i class="fa-solid fa-folder-tree"></i>
        </div>
        <div class="metric-info">
            <span class="metric-value">{{ $kpis->total ?? 0 }}</span>
            <span class="metric-label">Tickets Totales</span>
        </div>
    </div>
    <div class="metric-card metric-open">
        <div class="metric-icon">
            <i class="fa-solid fa-door-open"></i>
        </div>
        <div class="metric-info">
            <span class="metric-value">{{ $kpis->abiertos ?? 0 }}</span>
            <span class="metric-label">Abiertos</span>
        </div>
    </div>
    <div class="metric-card metric-progress">
        <div class="metric-icon">
            <i class="fa-solid fa-spinner fa-spin-pulse"></i>
        </div>
        <div class="metric-info">
            <span class="metric-value">{{ $kpis->en_progreso ?? 0 }}</span>
            <span class="metric-label">En Progreso</span>
        </div>
    </div>
    <div class="metric-card metric-resolved">
        <div class="metric-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="metric-info">
            <span class="metric-value">{{ ($kpis->resueltos ?? 0) + ($kpis->cerrados ?? 0) }}</span>
            <span class="metric-label">Resueltos / Cerrados</span>
        </div>
    </div>
    <div class="metric-card metric-vencido">
        <div class="metric-icon">
            <i class="fa-solid fa-hourglass-alert"></i>
        </div>
        <div class="metric-info">
            <span class="metric-value">{{ $vencidosCount }}</span>
            <span class="metric-label">Vencidos (SLA)</span>
        </div>
    </div>
</div>

<!-- Two-Column Grid: Technicians Load vs System Audit -->
<div class="ticket-detail-grid">
    <!-- Column 1: Technicians -->
    <div class="card" style="padding: 1.5rem;">
        <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-white); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fa-solid fa-users-gear" style="color: var(--primary);"></i>
            <span>Carga de Trabajo del Equipo Técnico</span>
        </h3>

        <div class="table-responsive">
            <table class="custom-table" style="font-size: 0.9rem;">
                <thead>
                    <tr>
                        <th>Técnico</th>
                        <th style="text-align: center;">Tickets Activos</th>
                        <th style="text-align: center;">Resueltos</th>
                        <th style="text-align: center;">T. Promedio</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($estadisticasTecnicos as $tecnico)
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: var(--text-white);">{{ $tecnico->nombre }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $tecnico->email }}</div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge" style="font-size: 0.85rem; padding: 0.25rem 0.6rem; border-radius: 6px; background: {{ $tecnico->tickets_activos > 3 ? 'rgba(248, 113, 113, 0.15)' : 'rgba(99, 102, 241, 0.1)' }}; color: {{ $tecnico->tickets_activos > 3 ? 'var(--clr-critica)' : 'var(--text-white)' }};">
                                    {{ $tecnico->tickets_activos }} act.
                                </span>
                            </td>
                            <td style="text-align: center; color: var(--clr-abierto); font-weight: 700;">
                                {{ $tecnico->tickets_resueltos_total }}
                            </td>
                            <td style="text-align: center; color: var(--text-muted);">
                                @if($tecnico->promedio_horas_resolucion)
                                    {{ round($tecnico->promedio_horas_resolucion, 1) }} hrs
                                @else
                                    <span style="font-style: italic; font-size: 0.8rem;">Sin datos</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">
                                No hay técnicos configurados en el sistema.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Column 2: Audit Logs -->
    <div class="card" style="padding: 1.5rem;">
        <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-white); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fa-solid fa-shield-halved" style="color: var(--clr-espera);"></i>
            <span>Historial de Auditoría (Sistema)</span>
        </h3>

        <div class="audit-list">
            @forelse($auditorias as $audit)
                <div class="audit-item" style="border-left-color: {{ 
                    $audit->accion === 'creado' ? 'var(--clr-abierto)' : (
                    $audit->accion === 'estado_cambiado' ? 'var(--clr-progreso)' : (
                    $audit->accion === 'prioridad_cambiada' ? 'var(--clr-alta)' : (
                    $audit->accion === 'auto_asignado' ? 'var(--primary)' : 'var(--clr-cerrado)'
                ))) }}">
                    <div class="audit-meta">
                        <strong>{{ $audit->actor_nombre ?? 'Sistema' }}</strong> 
                        &bull; {{ \Carbon\Carbon::parse($audit->created_at)->diffForHumans() }}
                        @if($audit->direccion_ip)
                            &bull; IP: <span style="font-family: monospace;">{{ $audit->direccion_ip }}</span>
                        @endif
                    </div>
                    <div class="audit-desc">{{ $audit->descripcion }}</div>
                </div>
            @empty
                <div style="text-align: center; color: var(--text-muted); padding: 3rem 0;">
                    No hay registros de auditoría en el sistema.
                </div>
            @endforelse
        </div>
        <div style="text-align: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-card);">
            <a href="{{ route('admin.auditoria') }}" class="btn btn-secondary" style="padding: 0.5rem 1.25rem; font-size: 0.85rem; border-radius: var(--radius-sm);">
                <i class="fa-solid fa-list-check"></i>
                <span>Ver Todo el Historial</span>
            </a>
        </div>
    </div>
</div>
@endsection
