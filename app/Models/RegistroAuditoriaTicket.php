<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroAuditoriaTicket extends Model
{
    use HasFactory;

    protected $table = 'registro_auditoria_tickets';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'ticket_id',
        'usuario_id',
        'accion',
        'valor_anterior',
        'valor_nuevo',
        'descripcion',
        'direccion_ip',
        'created_at',
    ];

    protected $casts = [
        'valor_anterior' => 'array',
        'valor_nuevo' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Ticket afectado por la auditoría.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Usuario que realizó la acción (actor).
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
