<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComentarioTicket extends Model
{
    use HasFactory;

    protected $table = 'comentarios_tickets';

    protected $fillable = [
        'ticket_id',
        'usuario_id',
        'cuerpo',
        'es_interno',
    ];

    protected $casts = [
        'es_interno' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Ticket al que pertenece el comentario.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Usuario que escribió el comentario.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
