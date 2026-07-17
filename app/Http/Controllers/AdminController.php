<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Muestra el dashboard general del administrador con KPIs, auditoría y carga de técnicos.
     */
    public function dashboard()
    {
        // KPIs de Tickets
        $kpis = DB::table('tickets')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN estado = "abierto" THEN 1 ELSE 0 END) as abiertos')
            ->selectRaw('SUM(CASE WHEN estado = "en_progreso" THEN 1 ELSE 0 END) as en_progreso')
            ->selectRaw('SUM(CASE WHEN estado = "en_espera" THEN 1 ELSE 0 END) as en_espera')
            ->selectRaw('SUM(CASE WHEN estado = "resuelto" THEN 1 ELSE 0 END) as resueltos')
            ->selectRaw('SUM(CASE WHEN estado = "cerrado" THEN 1 ELSE 0 END) as cerrados')
            ->first();

        // Tickets vencidos
        $vencidosCount = Ticket::whereNotIn('estado', ['resuelto', 'cerrado'])
            ->where('due_at', '<', now())
            ->count();

        // Cargar estadísticas de técnicos desde la vista MySQL
        $estadisticasTecnicos = DB::table('v_estadisticas_tecnicos')->get();

        // Obtener historial de auditoría reciente
        $auditorias = DB::table('registro_auditoria_tickets as al')
            ->leftJoin('usuarios as u', 'al.usuario_id', '=', 'u.id')
            ->select('al.*', 'u.nombre as actor_nombre')
            ->orderBy('al.created_at', 'desc')
            ->take(15)
            ->get();

        return view('dashboard.admin', compact('kpis', 'vencidosCount', 'estadisticasTecnicos', 'auditorias'));
    }

    /**
     * Reasignar un ticket manualmente a otro técnico.
     */
    public function reasignar(Request $request, Ticket $ticket, TicketService $ticketService)
    {
        $request->validate([
            'tecnico_id' => 'nullable|exists:usuarios,id',
        ]);

        $tecnicoId = $request->tecnico_id;
        
        // Si se selecciona técnico, validar que tenga el rol correspondiente
        if ($tecnicoId) {
            $tecnico = User::findOrFail($tecnicoId);
            if (!$tecnico->esTecnico()) {
                return back()->withErrors(['tecnico_id' => 'El usuario seleccionado debe ser un técnico.']);
            }
        }

        $ticketService->reasignarTicket($ticket, $tecnicoId, Auth::user(), $request->ip());

        return back()->with('success', 'Ticket reasignado correctamente.');
    }

    /**
     * Muestra el registro de auditoría completo del sistema de forma paginada.
     */
    public function auditoria(Request $request)
    {
        $auditorias = DB::table('registro_auditoria_tickets as al')
            ->leftJoin('usuarios as u', 'al.usuario_id', '=', 'u.id')
            ->leftJoin('tickets as t', 'al.ticket_id', '=', 't.id')
            ->select('al.*', 'u.nombre as actor_nombre', 't.numero_ticket')
            ->orderBy('al.created_at', 'desc')
            ->paginate(20);

        return view('dashboard.admin_auditoria', compact('auditorias'));
    }
}
