<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchivoAdjuntoTicket extends Model
{
    use HasFactory;

    protected $table = 'archivos_adjuntos_tickets';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'ticket_id',
        'usuario_id',
        'nombre_original',
        'ruta',
        'tipo_mime',
        'tamano_bytes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Ticket al que pertenece el archivo adjunto.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Usuario que subió el archivo.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
