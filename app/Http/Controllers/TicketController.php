<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Categoria;
use App\Models\Prioridad;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * Muestra la lista de tickets según el rol con filtros aplicados.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Ticket::with(['categoria', 'prioridad', 'cliente', 'tecnico']);

        // Scope del rol
        if ($user->esCliente()) {
            $query->where('usuario_id', $user->id);
        } elseif ($user->esTecnico()) {
            $query->where('asignado_a', $user->id);
        }

        // Filtro por Estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por Categoría
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Filtro por Prioridad
        if ($request->filled('prioridad_id')) {
            $query->where('prioridad_id', $request->prioridad_id);
        }

        // Búsqueda
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('numero_ticket', 'like', "%{$buscar}%")
                  ->orWhere('titulo', 'like', "%{$buscar}%")
                  ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        
        $categorias = Categoria::all();
        $prioridades = Prioridad::orderBy('orden')->get();

        $metricas = null;
        if ($user->esTecnico()) {
            $metricas = \Illuminate\Support\Facades\DB::table('tickets')
                ->where('asignado_a', $user->id)
                ->selectRaw('COALESCE(SUM(CASE WHEN estado IN ("abierto", "en_progreso", "en_espera") THEN 1 ELSE 0 END), 0) as activos')
                ->selectRaw('COALESCE(SUM(CASE WHEN estado = "en_progreso" THEN 1 ELSE 0 END), 0) as en_progreso')
                ->selectRaw('COALESCE(SUM(CASE WHEN estado IN ("resuelto", "cerrado") THEN 1 ELSE 0 END), 0) as resueltos')
                ->selectRaw('COALESCE(SUM(CASE WHEN estado NOT IN ("resuelto", "cerrado") AND due_at < ? THEN 1 ELSE 0 END), 0) as vencidos', [now()])
                ->first();
        }

        // Determinar a qué vista de dashboard ir según el rol
        if ($user->esAdmin()) {
            return view('dashboard.admin_tickets', compact('tickets', 'categorias', 'prioridades'));
        } elseif ($user->esTecnico()) {
            return view('dashboard.tecnico', compact('tickets', 'categorias', 'prioridades', 'metricas'));
        } else {
            return view('dashboard.cliente', compact('tickets', 'categorias', 'prioridades'));
        }
    }

    /**
     * Muestra el formulario para crear un ticket.
     */
    public function create()
    {
        $categorias = Categoria::all();
        $prioridades = Prioridad::orderBy('orden')->get();
        return view('tickets.create', compact('categorias', 'prioridades'));
    }

    /**
     * Registra un nuevo ticket en la BD.
     */
    public function store(Request $request, TicketService $ticketService)
    {
        $data = $request->validate([
            'titulo' => 'required|string|min:8|max:255',
            'descripcion' => 'required|string|min:15',
            'categoria_id' => 'required|exists:categorias,id',
            'prioridad_id' => 'required|exists:prioridades,id',
            'adjuntos.*' => 'nullable|file|max:10240', // Max 10MB por archivo
        ], [
            'titulo.required' => 'El asunto del ticket es obligatorio.',
            'titulo.min' => 'El asunto del ticket debe tener al menos 8 caracteres para ser descriptivo.',
            'descripcion.required' => 'La descripción detallada del problema es obligatoria.',
            'descripcion.min' => 'Describe el problema con mayor detalle (mínimo 15 caracteres).',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'prioridad_id.required' => 'La prioridad es obligatoria.',
            'adjuntos.*.max' => 'Los archivos adjuntos no deben superar los 10MB.',
        ]);

        $archivos = $request->file('adjuntos', []);
        $ticket = $ticketService->crearTicket($data, Auth::user(), $archivos, $request->ip());

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', "Ticket {$ticket->numero_ticket} creado con éxito y asignado al soporte.");
    }

    /**
     * Muestra el detalle de un ticket.
     */
    public function show(Ticket $ticket)
    {
        $user = Auth::user();

        // Validar acceso del cliente
        if ($user->esCliente() && $ticket->usuario_id !== $user->id) {
            abort(403, 'No tienes permiso para ver este ticket.');
        }

        // Cargar relaciones
        $ticket->load([
            'categoria', 
            'prioridad', 
            'cliente', 
            'tecnico', 
            'adjuntos.usuario', 
            'comentarios' => function ($q) use ($user) {
                // Si el usuario es cliente, filtrar comentarios internos
                if ($user->esCliente()) {
                    $q->where('es_interno', 0);
                }
                $q->with('usuario')->orderBy('created_at', 'asc');
            },
            'auditorias.usuario'
        ]);

        $tecnicos = [];
        $prioridades = [];
        if (!$user->esCliente()) {
            $prioridades = Prioridad::orderBy('orden')->get();
        }
        if ($user->esAdmin()) {
            // Técnicos activos para el selector de asignación
            $tecnicos = User::where('rol', 'tecnico')->where('activo', 1)->get();
        }

        return view('tickets.show', compact('ticket', 'tecnicos', 'prioridades'));
    }

    /**
     * Actualiza el estado de un ticket.
     */
    public function updateEstado(Request $request, Ticket $ticket, TicketService $ticketService)
    {
        $user = Auth::user();

        // Validar permisos de técnico o administrador
        if ($user->esCliente()) {
            abort(403, 'No tienes autorización para cambiar el estado.');
        }

        if ($user->esTecnico() && $ticket->asignado_a !== $user->id) {
            abort(403, 'Solo el técnico asignado puede cambiar el estado de este ticket.');
        }

        $request->validate([
            'estado' => 'required|in:abierto,en_progreso,en_espera,resuelto,cerrado',
            'notas_resolucion' => 'nullable|string',
        ]);

        $ticketService->cambiarEstado(
            $ticket, 
            $request->estado, 
            $user, 
            $request->notas_resolucion, 
            $request->ip()
        );

        return back()->with('success', 'El estado del ticket ha sido actualizado correctamente.');
    }

    /**
     * Actualiza la prioridad de un ticket (Admin o Técnico).
     */
    public function updatePrioridad(Request $request, Ticket $ticket, TicketService $ticketService)
    {
        $user = Auth::user();

        if ($user->esCliente()) {
            abort(403, 'No tienes autorización para cambiar la prioridad.');
        }

        $request->validate([
            'prioridad_id' => 'required|exists:prioridades,id',
        ]);

        $ticketService->cambiarPrioridad($ticket, $request->prioridad_id, $user, $request->ip());

        return back()->with('success', 'La prioridad del ticket y el tiempo límite (SLA) han sido actualizados correctamente.');
    }
}
