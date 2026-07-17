<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRol
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Si el usuario no está activo, cerrar sesión e ir al login
        if (!$user->activo) {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta ha sido desactivada.',
            ]);
        }

        // Si el rol del usuario está dentro de los permitidos, continuar
        if (in_array($user->rol, $roles)) {
            return $next($request);
        }

        abort(403, 'Acceso denegado: No tienes los permisos requeridos para acceder a esta sección.');
    }
}
