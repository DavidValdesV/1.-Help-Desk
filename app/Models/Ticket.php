<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'tickets';

    protected $fillable = [
        'numero_ticket',
        'usuario_id',
        'categoria_id',
        'prioridad_id',
        'asignado_a',
        'titulo',
        'descripcion',
        'estado',
        'notas_resolucion',
        'due_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Cliente que abrió el ticket.
     */
    public function cliente()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Técnico asignado.
     */
    public function tecnico()
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    /**
     * Categoría del ticket.
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Prioridad y SLA.
     */
    public function prioridad()
    {
        return $this->belongsTo(Prioridad::class, 'prioridad_id');
    }

    /**
     * Comentarios públicos e internos del ticket.
     */
    public function comentarios()
    {
        return $this->hasMany(ComentarioTicket::class, 'ticket_id');
    }

    /**
     * Historial de auditoría de cambios del ticket.
     */
    public function auditorias()
    {
        return $this->hasMany(RegistroAuditoriaTicket::class, 'ticket_id');
    }

    /**
     * Archivos adjuntos del ticket.
     */
    public function adjuntos()
    {
        return $this->hasMany(ArchivoAdjuntoTicket::class, 'ticket_id');
    }

    /**
     * Verifica si el ticket está vencido (SLA superado).
     */
    public function estaVencido(): bool
    {
        if (in_array($this->estado, ['resuelto', 'cerrado'])) {
            return false;
        }
        return $this->due_at && $this->due_at->isPast();
    }

    /**
     * Fecha/hora en la que el ticket resuelto se cerrará automáticamente
     * si el cliente no responde. Devuelve null si no aplica.
     */
    public function fechaAutoCierre(): ?\Illuminate\Support\Carbon
    {
        if ($this->estado !== 'resuelto' || !$this->resolved_at) {
            return null;
        }

        return $this->resolved_at->copy()->addMinutes(config('helpdesk.auto_cierre_minutos', 10));
    }

    /**
     * Tickets resueltos cuyo plazo de espera expiró y en los que el cliente
     * no publicó ningún comentario desde la resolución.
     */
    public function scopePendientesDeAutoCierre($query)
    {
        $limite = now()->subMinutes(config('helpdesk.auto_cierre_minutos', 10));

        return $query->where('estado', 'resuelto')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', $limite)
            ->whereDoesntHave('comentarios', function ($q) {
                $q->whereColumn('comentarios_tickets.usuario_id', 'tickets.usuario_id')
                  ->whereColumn('comentarios_tickets.created_at', '>=', 'tickets.resolved_at');
            });
    }
}
