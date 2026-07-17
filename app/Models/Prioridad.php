<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prioridad extends Model
{
    use HasFactory;

    protected $table = 'prioridades';

    // No tiene timestamps en el script SQL
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'color',
        'horas_sla',
        'orden',
    ];

    /**
     * Obtiene los tickets asociados a esta prioridad.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'prioridad_id');
    }
}
