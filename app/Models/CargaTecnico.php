<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CargaTecnico extends Model
{
    use HasFactory;

    protected $table = 'carga_tecnicos';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'tickets_activos',
    ];

    protected $casts = [
        'tickets_activos' => 'integer',
        'updated_at' => 'datetime',
    ];

    /**
     * Técnico asociado a esta carga.
     */
    public function tecnico()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
