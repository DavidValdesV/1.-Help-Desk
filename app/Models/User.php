<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'usuarios';

    /**
     * Atributos asignables de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'email',
        'password',
        'rol',
        'departamento',
        'telefono',
        'avatar',
        'activo',
        'email_verified_at',
    ];

    /**
     * Atributos ocultos en la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversión de tipos de atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'activo' => 'boolean',
    ];

    /**
     * Obtiene los tickets creados por el usuario (rol cliente).
     */
    public function ticketsCreados()
    {
        return $this->hasMany(Ticket::class, 'usuario_id');
    }

    /**
     * Obtiene los tickets asignados al usuario (rol tecnico).
     */
    public function ticketsAsignados()
    {
        return $this->hasMany(Ticket::class, 'asignado_a');
    }

    /**
     * Obtiene los comentarios del usuario.
     */
    public function comentarios()
    {
        return $this->hasMany(ComentarioTicket::class, 'usuario_id');
    }

    /**
     * Obtiene la carga de trabajo activa si el usuario es técnico.
     */
    public function carga()
    {
        return $this->hasOne(CargaTecnico::class, 'usuario_id');
    }

    /**
     * Verificar si el usuario tiene rol de Administrador.
     */
    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    /**
     * Verificar si el usuario tiene rol de Técnico.
     */
    public function esTecnico(): bool
    {
        return $this->rol === 'tecnico';
    }

    /**
     * Verificar si el usuario tiene rol de Cliente.
     */
    public function esCliente(): bool
    {
        return $this->rol === 'cliente';
    }
}
