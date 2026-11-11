<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\ComentarioTicket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Guarda un comentario en el ticket.
     */
    public function store(Request $request, Ticket $ticket, TicketService $ticketService)
    {
        $user = Auth::user();

        // Validar acceso del cliente o técnico asignado al ticket
        if (!$ticket->usuarioPuedeAcceder($user)) {
            abort(403, 'No tienes permiso para comentar en este ticket.');
        }

        // Respaldo: si el plazo de cierre automático ya expiró, el ticket queda cerrado
        $ticketService->cerrarSiExpirado($ticket);

        // Si el ticket está cerrado, el cliente no puede comentar
        if ($ticket->estado === 'cerrado' && $user->esCliente()) {
            abort(403, 'No puedes comentar en un ticket que ha sido cerrado.');
        }

        $request->validate([
            'cuerpo' => 'required|string',
            'es_interno' => 'nullable|boolean',
        ], [
            'cuerpo.required' => 'El cuerpo del comentario no puede estar vacío.',
        ]);

        // Determinar si es un comentario interno
        // Solo administradores y técnicos pueden publicar notas internas
        $esInterno = 0;
        if (!$user->esCliente() && $request->has('es_interno')) {
            $esInterno = $request->es_interno ? 1 : 0;
        }

        ComentarioTicket::create([
            'ticket_id' => $ticket->id,
            'usuario_id' => $user->id,
            'cuerpo' => $request->cuerpo,
            'es_interno' => $esInterno,
        ]);

        // Si el cliente responde a un ticket resuelto (dentro del plazo de cierre automático),
        // el ticket se reabre para que el técnico lo revise nuevamente.
        if ($ticket->estado === 'resuelto' && $user->id === $ticket->usuario_id) {
            $ticketService->cambiarEstado($ticket, 'en_progreso', $user, null, $request->ip());

            return back()->with('success', 'Comentario publicado. El ticket fue reabierto para que soporte lo revise.');
        }

        $mensaje = $esInterno ? 'Nota interna guardada con éxito.' : 'Comentario publicado con éxito.';

        return back()->with('success', $mensaje);
    }
}
