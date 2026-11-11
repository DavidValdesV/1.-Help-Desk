<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Prioridad;
use App\Models\RegistroAuditoriaTicket;
use App\Models\ArchivoAdjuntoTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketService
{
    /**
     * Crear un ticket nuevo, calcula SLA, ejecuta asignación automática y registra la auditoría.
     */
    public function crearTicket(array $data, User $cliente, array $archivos = [], ?string $ip = null): Ticket
    {
        return DB::transaction(function () use ($data, $cliente, $archivos, $ip) {
            $prioridad = Prioridad::findOrFail($data['prioridad_id']);
            $due_at = now()->addHours($prioridad->horas_sla);

            // Generar número correlativo de ticket: TKT-YYYY-0001
            $year = now()->format('Y');
            $ultimoNumero = Ticket::where('numero_ticket', 'like', "TKT-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('numero_ticket')
                ->value('numero_ticket');
            $count = $ultimoNumero ? ((int) substr($ultimoNumero, -4)) + 1 : 1;
            $numeroTicket = sprintf("TKT-%s-%04d", $year, $count);

            // Buscar técnico con la menor carga de trabajo activa
            $tecnicoCarga = DB::table('usuarios')
                ->join('carga_tecnicos', 'usuarios.id', '=', 'carga_tecnicos.usuario_id')
                ->where('usuarios.rol', 'tecnico')
                ->where('usuarios.activo', 1)
                ->orderBy('carga_tecnicos.tickets_activos', 'asc')
                ->orderBy('usuarios.id')
                ->lockForUpdate()
                ->select('usuarios.id', 'usuarios.nombre', 'carga_tecnicos.tickets_activos')
                ->first();

            $tecnicoAsignadoId = null;
            if ($tecnicoCarga) {
                $tecnicoAsignadoId = $tecnicoCarga->id;
            }

            // Crear el ticket
            $ticket = Ticket::create([
                'numero_ticket' => $numeroTicket,
                'usuario_id' => $cliente->id,
                'categoria_id' => $data['categoria_id'],
                'prioridad_id' => $data['prioridad_id'],
                'asignado_a' => $tecnicoAsignadoId,
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'],
                'estado' => 'abierto',
                'due_at' => $due_at,
            ]);

            // Incrementar carga de trabajo del técnico si fue asignado
            if ($tecnicoAsignadoId) {
                DB::table('carga_tecnicos')
                    ->where('usuario_id', $tecnicoAsignadoId)
                    ->increment('tickets_activos');
            }

            // Auditoría: Creación del ticket
            RegistroAuditoriaTicket::create([
                'ticket_id' => $ticket->id,
                'usuario_id' => $cliente->id,
                'accion' => 'creado',
                'valor_anterior' => null,
                'valor_nuevo' => [
                    'estado' => 'abierto',
                    'prioridad' => $prioridad->nombre
                ],
                'descripcion' => "{$cliente->nombre} creó el ticket {$ticket->numero_ticket}",
                'direccion_ip' => $ip,
            ]);

            // Auditoría: Asignación automática
            if ($tecnicoCarga) {
                RegistroAuditoriaTicket::create([
                    'ticket_id' => $ticket->id,
                    'usuario_id' => null, // Acción automática del sistema
                    'accion' => 'auto_asignado',
                    'valor_anterior' => ['asignado_a' => null],
                    'valor_nuevo' => ['asignado_a' => $tecnicoCarga->nombre],
                    'descripcion' => "Sistema asignó automáticamente a {$tecnicoCarga->nombre} (menor carga: {$tecnicoCarga->tickets_activos} tickets activos)",
                    'direccion_ip' => null,
                ]);
            }

            // Guardar archivos adjuntos si los hay (en almacenamiento privado 'local')
            foreach ($archivos as $archivo) {
                if ($archivo->isValid()) {
                    $ruta = $archivo->store('adjuntos', 'local');
                    ArchivoAdjuntoTicket::create([
                        'ticket_id' => $ticket->id,
                        'usuario_id' => $cliente->id,
                        'nombre_original' => $archivo->getClientOriginalName(),
                        'ruta' => $ruta,
                        'tipo_mime' => $archivo->getMimeType(),
                        'tamano_bytes' => $archivo->getSize(),
                    ]);
                }
            }

            return $ticket;
        });
    }

    /**
     * Cambiar el estado de un ticket y actualizar la carga de trabajo del técnico asignado de forma coherente.
     * Si $actor es null, el cambio se registra como una acción automática del sistema.
     */
    public function cambiarEstado(Ticket $ticket, string $nuevoEstado, ?User $actor, ?string $notasResolucion = null, ?string $ip = null, string $accionAuditoria = 'estado_cambiado'): Ticket
    {
        return DB::transaction(function () use ($ticket, $nuevoEstado, $actor, $ip, $notasResolucion, $accionAuditoria) {
            $estadoAnterior = $ticket->estado;

            if ($estadoAnterior === $nuevoEstado) {
                return $ticket;
            }

            $estadosActivos = ['abierto', 'en_progreso', 'en_espera'];
            $estadosInactivos = ['resuelto', 'cerrado'];

            // Caso 1: Pasa de ACTIVO a INACTIVO (Resolución/Cierre)
            if (in_array($estadoAnterior, $estadosActivos) && in_array($nuevoEstado, $estadosInactivos)) {
                if ($ticket->asignado_a) {
                    DB::table('carga_tecnicos')
                        ->where('usuario_id', $ticket->asignado_a)
                        ->where('tickets_activos', '>', 0)
                        ->decrement('tickets_activos');
                }
            }
            // Caso 2: Pasa de INACTIVO a ACTIVO (Reapertura)
            elseif (in_array($estadoAnterior, $estadosInactivos) && in_array($nuevoEstado, $estadosActivos)) {
                if ($ticket->asignado_a) {
                    DB::table('carga_tecnicos')
                        ->where('usuario_id', $ticket->asignado_a)
                        ->increment('tickets_activos');
                }

                // Limpiar fechas de resolución/cierre correspondientes
                if ($estadoAnterior === 'resuelto') {
                    $ticket->resolved_at = null;
                }
            }

            // Fechas de resolución y cierre (aplica también a transiciones resuelto <-> cerrado).
            // Al marcar como resuelto se reinicia el plazo de cierre automático.
            if ($nuevoEstado === 'resuelto') {
                $ticket->resolved_at = now();
                $ticket->closed_at = null;
            } elseif ($nuevoEstado === 'cerrado') {
                $ticket->closed_at = now();
            }

            // Actualizar estado y notas
            $ticket->estado = $nuevoEstado;
            if ($notasResolucion !== null) {
                $ticket->notas_resolucion = $notasResolucion;
            }
            $ticket->save();

            // Auditoría
            $estadoTextos = [
                'abierto' => 'Abierto',
                'en_progreso' => 'En Progreso',
                'en_espera' => 'En Espera',
                'resuelto' => 'Resuelto',
                'cerrado' => 'Cerrado',
            ];

            $textoAnt = $estadoTextos[$estadoAnterior] ?? $estadoAnterior;
            $textoNvo = $estadoTextos[$nuevoEstado] ?? $nuevoEstado;

            $nombreActor = $actor->nombre ?? 'Sistema';
            $descripcion = $accionAuditoria === 'auto_cerrado'
                ? "Sistema cerró automáticamente el ticket: el cliente no respondió en " . config('helpdesk.auto_cierre_minutos', 10) . " minutos tras la resolución"
                : "{$nombreActor} cambió el estado de {$textoAnt} a {$textoNvo}";

            RegistroAuditoriaTicket::create([
                'ticket_id' => $ticket->id,
                'usuario_id' => $actor?->id,
                'accion' => $accionAuditoria,
                'valor_anterior' => ['estado' => $estadoAnterior],
                'valor_nuevo' => ['estado' => $nuevoEstado],
                'descripcion' => $descripcion,
                'direccion_ip' => $ip,
            ]);

            return $ticket;
        });
    }

    /**
     * Cierra automáticamente los tickets resueltos cuyo plazo de espera expiró
     * sin que el cliente haya respondido. Devuelve la cantidad de tickets cerrados.
     */
    public function cerrarTicketsResueltosSinRespuesta(): int
    {
        $cerrados = 0;

        Ticket::pendientesDeAutoCierre()->each(function (Ticket $ticket) use (&$cerrados) {
            $this->cambiarEstado($ticket, 'cerrado', null, null, null, 'auto_cerrado');
            $cerrados++;
        });

        return $cerrados;
    }

    /**
     * Cierra un ticket puntual si su plazo de cierre automático ya expiró.
     * Sirve como respaldo cuando el scheduler no está corriendo.
     */
    public function cerrarSiExpirado(Ticket $ticket): Ticket
    {
        if (Ticket::pendientesDeAutoCierre()->whereKey($ticket->id)->exists()) {
            $this->cambiarEstado($ticket, 'cerrado', null, null, null, 'auto_cerrado');
        }

        return $ticket;
    }

    /**
     * Reasignar manualmente un ticket a otro técnico y actualizar las cargas de trabajo de manera atómica.
     */
    public function reasignarTicket(Ticket $ticket, ?int $nuevoTecnicoId, User $actor, ?string $ip = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $nuevoTecnicoId, $actor, $ip) {
            $tecnicoAnteriorId = $ticket->asignado_a;

            if ($tecnicoAnteriorId == $nuevoTecnicoId) {
                return $ticket;
            }

            $estadosActivos = ['abierto', 'en_progreso', 'en_espera'];
            $esTicketActivo = in_array($ticket->estado, $estadosActivos);

            // Obtener nombres para auditoría
            $tecnicoAnteriorNombre = 'Sin asignar';
            if ($tecnicoAnteriorId) {
                $tecnicoAnteriorNombre = User::find($tecnicoAnteriorId)->nombre ?? 'Desconocido';
            }

            $tecnicoNuevoNombre = 'Sin asignar';
            if ($nuevoTecnicoId) {
                $tecnicoNuevoNombre = User::find($nuevoTecnicoId)->nombre ?? 'Desconocido';
            }

            // Si el ticket está en estado activo, debemos transferir la carga de trabajo
            if ($esTicketActivo) {
                // Quitar carga del técnico anterior
                if ($tecnicoAnteriorId) {
                    DB::table('carga_tecnicos')
                        ->where('usuario_id', $tecnicoAnteriorId)
                        ->where('tickets_activos', '>', 0)
                        ->decrement('tickets_activos');
                }

                // Añadir carga al técnico nuevo
                if ($nuevoTecnicoId) {
                    DB::table('carga_tecnicos')
                        ->where('usuario_id', $nuevoTecnicoId)
                        ->increment('tickets_activos');
                }
            }

            // Actualizar la asignación del ticket
            $ticket->asignado_a = $nuevoTecnicoId;
            $ticket->save();

            // Auditoría
            RegistroAuditoriaTicket::create([
                'ticket_id' => $ticket->id,
                'usuario_id' => $actor->id,
                'accion' => 'asignado',
                'valor_anterior' => ['asignado_a' => $tecnicoAnteriorNombre],
                'valor_nuevo' => ['asignado_a' => $tecnicoNuevoNombre],
                'descripcion' => $nuevoTecnicoId 
                    ? "{$actor->nombre} asignó el ticket a {$tecnicoNuevoNombre} manualmente"
                    : "{$actor->nombre} quitó la asignación del ticket",
                'direccion_ip' => $ip,
            ]);

            return $ticket;
        });
    }

    /**
     * Permite a un técnico o administrador cambiar la prioridad de un ticket y recalcula el SLA (due_at).
     */
    public function cambiarPrioridad(Ticket $ticket, int $nuevaPrioridadId, User $actor, ?string $ip = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $nuevaPrioridadId, $actor, $ip) {
            $prioridadAnteriorId = $ticket->prioridad_id;

            if ($prioridadAnteriorId == $nuevaPrioridadId) {
                return $ticket;
            }

            $prioridadAnterior = Prioridad::find($prioridadAnteriorId);
            $prioridadNueva = Prioridad::findOrFail($nuevaPrioridadId);

            // Recalcular due_at basándose en la fecha de creación del ticket + horas_sla de la nueva prioridad
            $ticket->prioridad_id = $nuevaPrioridadId;
            $ticket->due_at = $ticket->created_at->addHours($prioridadNueva->horas_sla);
            $ticket->save();

            // Auditoría
            RegistroAuditoriaTicket::create([
                'ticket_id' => $ticket->id,
                'usuario_id' => $actor->id,
                'accion' => 'prioridad_cambiada',
                'valor_anterior' => ['prioridad' => $prioridadAnterior->nombre ?? 'Desconocida'],
                'valor_nuevo' => ['prioridad' => $prioridadNueva->nombre],
                'descripcion' => "{$actor->nombre} cambió la prioridad de " . ($prioridadAnterior->nombre ?? 'Desconocida') . " a {$prioridadNueva->nombre}",
                'direccion_ip' => $ip,
            ]);

            return $ticket;
        });
    }
}
