@extends('layouts.app')

@section('title', 'Detalles de Ticket ' . $ticket->numero_ticket)

@section('content')
<div class="header-bar">
    <div class="page-title">
        <h1 style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="color: var(--text-muted); font-size: 1.5rem;">Ticket</span> 
            <span>{{ $ticket->numero_ticket }}</span>
        </h1>
        <p>Reportado por {{ $ticket->cliente->nombre }} &bull; {{ $ticket->cliente->departamento }}</p>
    </div>
    <div>
        @if(auth()->user()->esAdmin())
            <a href="{{ route('admin.tickets') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Volver a la lista</span>
            </a>
        @elseif(auth()->user()->esTecnico())
            <a href="{{ route('tecnico.dashboard') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Volver a mis tickets</span>
            </a>
        @else
            <a href="{{ route('cliente.dashboard') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Volver a mis tickets</span>
            </a>
        @endif
    </div>
</div>

<div class="ticket-detail-grid">
    <!-- Left Column: Details, Attachments, Comments -->
    <div class="ticket-chat-section">
        <!-- Ticket Core Description -->
        <div class="card">
            <h2 style="font-size: 1.4rem; color: var(--text-white); margin-bottom: 1rem; font-weight: 700;">
                {{ $ticket->titulo }}
            </h2>
            <div style="background: rgba(0,0,0,0.2); border-radius: var(--radius-md); padding: 1.25rem; font-size: 0.95rem; line-height: 1.7; white-space: pre-wrap; border: 1px solid rgba(255,255,255,0.03);">
                {{ $ticket->descripcion }}
            </div>

            <!-- Attachments List -->
            @if($ticket->adjuntos->isNotEmpty())
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-card);">
                    <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--text-white); margin-bottom: 0.75rem;">
                        <i class="fa-solid fa-paperclip" style="color: var(--primary); margin-right: 0.5rem;"></i>
                        <span>Archivos Adjuntos ({{ $ticket->adjuntos->count() }})</span>
                    </h4>
                    <div class="attachments-grid">
                        @foreach($ticket->adjuntos as $adjunto)
                            <div class="attachment-file">
                                <i class="fa-solid fa-file-shield"></i>
                                <div style="flex-grow: 1; min-width: 0;">
                                    <div style="font-weight: 600; color: var(--text-white); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $adjunto->nombre_original }}
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        {{ round($adjunto->tamano_bytes / 1024, 1) }} KB &bull; Subido por {{ $adjunto->usuario->nombre }}
                                    </div>
                                </div>
                                <a href="{{ asset('storage/' . $adjunto->ruta) }}" target="_blank" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: var(--radius-sm);">
                                    <i class="fa-solid fa-download"></i>
                                    <span>Ver</span>
                                </a>
                            </div>
                        @endforeach
                    </div>

                    <!-- Vista previa de imágenes -->
                    @php
                        $imagenes = $ticket->adjuntos->filter(fn($adj) => \Illuminate\Support\Str::startsWith($adj->tipo_mime, 'image/'));
                    @endphp
                    @if($imagenes->isNotEmpty())
                        <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px dashed rgba(255,255,255,0.06);">
                            <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-white); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fa-solid fa-images" style="color: var(--primary);"></i>
                                <span>Capturas de Pantalla / Evidencia</span>
                            </div>
                            <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                                @foreach($imagenes as $img)
                                    <div style="flex: 1; min-width: 150px; max-width: 260px; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--border-card); background: rgba(0,0,0,0.15); padding: 0.5rem;">
                                        <a href="{{ asset('storage/' . $img->ruta) }}" target="_blank" title="Ver a tamaño completo">
                                            <img src="{{ asset('storage/' . $img->ruta) }}" alt="{{ $img->nombre_original }}" style="width: 100%; height: auto; max-height: 150px; object-fit: contain; border-radius: 4px; display: block;">
                                        </a>
                                        <div style="font-size: 0.75rem; color: var(--text-muted); text-align: center; margin-top: 0.5rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $img->nombre_original }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Chat / Comments Thread -->
        <div class="card">
            <h3 style="font-size: 1.1rem; color: var(--text-white); margin-bottom: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-comments" style="color: var(--primary);"></i>
                <span>Comentarios y Notas de Resolución</span>
            </h3>

            <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-bottom: 2rem;">
                <!-- System generated resolution notes (if resolved) -->
                @if($ticket->notas_resolucion)
                    <div class="chat-bubble" style="background: rgba(52, 211, 153, 0.05); border: 1px solid rgba(52, 211, 153, 0.25); border-left: 4px solid var(--clr-abierto);">
                        <div class="chat-bubble-header">
                            <span class="chat-bubble-author" style="color: var(--clr-abierto);">
                                <i class="fa-solid fa-circle-check"></i> Nota de Resolución Oficial
                            </span>
                            <span>{{ $ticket->resolved_at ? $ticket->resolved_at->format('d/m/Y H:i') : '' }}</span>
                        </div>
                        <div class="chat-bubble-body" style="font-weight: 600;">
                            {{ $ticket->notas_resolucion }}
                        </div>
                    </div>
                @endif

                @forelse($ticket->comentarios as $comentario)
                    <div class="chat-bubble {{ $comentario->es_interno ? 'interno' : '' }}">
                        <div class="chat-bubble-header">
                            <span class="chat-bubble-author">
                                {{ $comentario->usuario->nombre }} 
                                <span style="font-size: 0.7rem; font-weight: 500; padding: 0.1rem 0.4rem; border-radius: 4px; background: rgba(255,255,255,0.06); text-transform: uppercase; margin-left: 0.25rem;">
                                    {{ $comentario->usuario->rol }}
                                </span>
                            </span>
                            <span>{{ $comentario->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="chat-bubble-body">
                            {{ $comentario->cuerpo }}
                        </div>
                    </div>
                @empty
                    @if(!$ticket->notas_resolucion)
                        <div style="text-align: center; color: var(--text-muted); padding: 2rem 0;">
                            No hay comentarios en este ticket aún. ¡Escribe el primero abajo!
                        </div>
                    @endif
                @endforelse
            </div>

            <!-- Comment Form -->
            @if($ticket->estado !== 'cerrado' || !auth()->user()->esCliente())
                <form action="{{ route('comments.store', $ticket->id) }}" method="POST" style="border-top: 1px solid var(--border-card); padding-top: 1.5rem;">
                    @csrf
                    <div class="form-group">
                        <label for="cuerpo" class="form-label">Escribir un Comentario</label>
                        <textarea name="cuerpo" id="cuerpo" rows="3" class="form-control" placeholder="Escribe tu respuesta aquí..." required></textarea>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                        <div>
                            <!-- Checkbox for internal note (Only visible for tech/admin) -->
                            @if(!auth()->user()->esCliente())
                                <label class="form-check">
                                    <input type="checkbox" name="es_interno" value="1" class="form-check-input">
                                    <span style="font-size: 0.85rem; color: var(--clr-espera); font-weight: 600;">
                                        <i class="fa-solid fa-user-lock"></i>
                                        <span>Nota interna (Privada para soporte)</span>
                                    </span>
                                </label>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Enviar Comentario</span>
                        </button>
                    </div>
                </form>
            @else
                <div style="border-top: 1px solid var(--border-card); padding-top: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.95rem; font-weight: 600;">
                    <i class="fa-solid fa-lock" style="margin-right: 0.35rem; color: var(--clr-critica);"></i> 
                    <span>Este ticket ha sido cerrado. No se admiten más comentarios de parte del cliente.</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Right Column: Ticket Sidebar Info & Actions -->
    <div class="ticket-sidebar-box">
        <!-- Summary State Card -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-white); margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-card); padding-bottom: 0.5rem;">
                Información del Ticket
            </h3>

            <div class="info-row">
                <span class="info-label">Estado</span>
                <span class="info-value">
                    <span class="badge badge-estado-{{ $ticket->estado }}">
                        {{ str_replace('_', ' ', $ticket->estado) }}
                    </span>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Prioridad</span>
                <span class="info-value">
                    <span class="badge badge-prioridad-{{ strtolower($ticket->prioridad->nombre ?? 'baja') }}">
                        {{ $ticket->prioridad->nombre ?? 'Baja' }}
                    </span>
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Categoría</span>
                <span class="info-value">
                    <i class="fa-solid {{ $ticket->categoria->icono ?? 'fa-circle-question' }}" style="color: var(--primary); margin-right: 0.35rem;"></i>
                    {{ $ticket->categoria->nombre }}
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">SLA Limite</span>
                <span class="info-value" style="color: {{ $ticket->estaVencido() ? 'var(--clr-critica)' : 'var(--text-white)' }}; font-weight: 700;">
                    {{ $ticket->due_at ? $ticket->due_at->format('d/m/Y H:i') : 'N/A' }}
                    @if($ticket->estaVencido())
                        <span style="font-size: 0.7rem; color: var(--clr-critica); display: block;">¡TICKET VENCIDO!</span>
                    @endif
                </span>
            </div>

            <div class="info-row">
                <span class="info-label">Creado el</span>
                <span class="info-value">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
            </div>

            <div class="info-row" style="border-bottom: none; padding-bottom: 0;">
                <span class="info-label">Técnico Asignado</span>
                <span class="info-value">
                    @if($ticket->tecnico)
                        <i class="fa-regular fa-user" style="margin-right: 0.35rem;"></i>
                        <strong>{{ $ticket->tecnico->nombre }}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.1rem;">
                            {{ $ticket->tecnico->departamento }}
                        </div>
                    @else
                        <span style="color: var(--text-muted); font-style: italic;">Sin asignar</span>
                    @endif
                </span>
            </div>
        </div>

        <!-- Actions / Workflow Transitions Card -->
        @if(!auth()->user()->esCliente())
            <div class="card" style="padding: 1.5rem;">
                <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-white); margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-card); padding-bottom: 0.5rem;">
                    Acciones de Soporte
                </h3>

                <!-- Reassignment form (Admin only) -->
                @if(auth()->user()->esAdmin())
                    <form action="{{ route('tickets.reasignar', $ticket->id) }}" method="POST" style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-card);">
                        @csrf
                        <div class="form-group">
                            <label for="tecnico_id" class="form-label">Asignación Manual (Técnico)</label>
                            <select name="tecnico_id" id="tecnico_id" class="form-control" onchange="this.form.submit()">
                                <option value="">-- Sin Asignar / Desasignar --</option>
                                @foreach($tecnicos as $tec)
                                    <option value="{{ $tec->id }}" {{ $ticket->asignado_a == $tec->id ? 'selected' : '' }}>
                                        {{ $tec->nombre }} (Activos: {{ $tec->carga->tickets_activos ?? 0 }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @endif

                <!-- Priority Adjustment form (Admin or Técnico) -->
                @if(!auth()->user()->esCliente())
                    <form action="{{ route('tickets.updatePrioridad', $ticket->id) }}" method="POST" style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-card);">
                        @csrf
                        <div class="form-group">
                            <label for="prioridad_id_select" class="form-label">Ajustar Prioridad (SLA)</label>
                            <select name="prioridad_id" id="prioridad_id_select" class="form-control" onchange="this.form.submit()">
                                @foreach($prioridades as $prio)
                                    <option value="{{ $prio->id }}" {{ $ticket->prioridad_id == $prio->id ? 'selected' : '' }}>
                                        {{ $prio->nombre }} (SLA: {{ $prio->horas_sla }}h)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @endif

                <!-- Status Update Form -->
                @if(auth()->user()->esAdmin() || (auth()->user()->esTecnico() && $ticket->asignado_a === auth()->user()->id))
                    @php
                        $postRoute = auth()->user()->esAdmin() 
                            ? route('admin.tickets.updateEstado', $ticket->id) 
                            : route('tickets.updateEstado', $ticket->id);
                    @endphp
                    <form action="{{ $postRoute }}" method="POST" id="form-estado">
                        @csrf
                        <div class="form-group">
                            <label for="estado" class="form-label">Cambiar Estado</label>
                            <select name="estado" id="estado-select" class="form-control" required onchange="toggleResolucionTextarea(this.value)">
                                <option value="abierto" {{ $ticket->estado === 'abierto' ? 'selected' : '' }}>Abierto</option>
                                <option value="en_progreso" {{ $ticket->estado === 'en_progreso' ? 'selected' : '' }}>En Progreso</option>
                                <option value="en_espera" {{ $ticket->estado === 'en_espera' ? 'selected' : '' }}>En Espera</option>
                                <option value="resuelto" {{ $ticket->estado === 'resuelto' ? 'selected' : '' }}>Resuelto</option>
                                <option value="cerrado" {{ $ticket->estado === 'cerrado' ? 'selected' : '' }}>Cerrado</option>
                            </select>
                        </div>

                        <!-- Resolution Notes Textarea (Hidden by default unless 'resuelto' selected) -->
                        <div class="form-group" id="resolucion-notes-group" style="display: {{ $ticket->estado === 'resuelto' ? 'block' : 'none' }};">
                            <label for="notas_resolucion" class="form-label" style="color: var(--clr-abierto); font-weight: 700;">
                                Diagnóstico / Notas de Resolución
                            </label>
                            <textarea name="notas_resolucion" id="notas_resolucion" rows="3" class="form-control" placeholder="Escribe el diagnóstico final, pasos de solución y recomendaciones...">{{ $ticket->notas_resolucion }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Guardar Cambios</span>
                        </button>
                    </form>
                @else
                    <div style="font-size: 0.85rem; color: var(--text-muted); text-align: center; padding: 0.5rem 0;">
                        <i class="fa-solid fa-lock" style="margin-right: 0.35rem;"></i> No eres el técnico asignado a este ticket. Solo puedes publicar comentarios o notas.
                    </div>
                @endif
            </div>
        @endif

        <!-- Ticket Audit Logs / Changes History (Auditoría) -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-white); margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-card); padding-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--clr-progreso); font-size: 0.95rem;"></i>
                <span>Historial del Ticket</span>
            </h3>

            <div class="audit-list" style="max-height: 250px;">
                @forelse($ticket->auditorias as $audit)
                    <div class="audit-item" style="border-left-width: 2px; padding-left: 0.65rem; margin-bottom: 0.5rem; border-left-color: {{ 
                        $audit->accion === 'creado' ? 'var(--clr-abierto)' : (
                        $audit->accion === 'estado_cambiado' ? 'var(--clr-progreso)' : (
                        $audit->accion === 'prioridad_cambiada' ? 'var(--clr-alta)' : (
                        $audit->accion === 'auto_asignado' ? 'var(--primary)' : 'var(--clr-cerrado)'
                    ))) }}">
                        <div class="audit-meta" style="font-size: 0.7rem;">
                            <strong>{{ $audit->usuario->nombre ?? 'Sistema' }}</strong> 
                            &bull; {{ $audit->created_at->format('d/m H:i') }}
                        </div>
                        <div class="audit-desc" style="font-size: 0.75rem; margin-top: 0.05rem; line-height: 1.3;">
                            {{ $audit->descripcion }}
                        </div>
                    </div>
                @empty
                    <div style="font-size: 0.8rem; text-align: center; color: var(--text-muted); padding: 1rem 0;">
                        Sin registros.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function toggleResolucionTextarea(value) {
        const group = document.getElementById('resolucion-notes-group');
        const textarea = document.getElementById('notas_resolucion');
        if (value === 'resuelto') {
            group.style.display = 'block';
            textarea.setAttribute('required', 'required');
        } else {
            group.style.display = 'none';
            textarea.removeAttribute('required');
        }
    }
</script>
@endsection
