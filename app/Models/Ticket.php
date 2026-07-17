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
}
