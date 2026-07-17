<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\ComentarioTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Guarda un comentario en el ticket.
     */
    public function store(Request $request, Ticket $ticket)
    {
        $user = Auth::user();

        // Validar acceso del cliente al ticket
        if ($user->esCliente() && $ticket->usuario_id !== $user->id) {
            abort(403, 'No tienes permiso para comentar en este ticket.');
        }

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

        $mensaje = $esInterno ? 'Nota interna guardada con éxito.' : 'Comentario publicado con éxito.';

        return back()->with('success', $mensaje);
    }
}
