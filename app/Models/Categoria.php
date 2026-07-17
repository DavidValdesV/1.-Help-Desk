<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    // Este modelo no tiene columna updated_at por el script
    public $timestamps = false;

    // Aunque podemos usar timestamps personalizados si lo requerimos.
    // El script define: `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`.
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'nombre',
        'descripcion',
        'icono',
    ];

    /**
     * Obtiene los tickets asociados a esta categoría.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'categoria_id');
    }
}
