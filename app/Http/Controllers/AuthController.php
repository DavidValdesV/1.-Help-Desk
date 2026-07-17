<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRol(Auth::user());
        }
        return view('auth.login');
    }

    /**
     * Procesa el inicio de sesión.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'Debes introducir un correo electrónico válido.',
            'password.required' => 'La contraseña es requerida.',
        ]);

        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Verificar si el usuario está activo
            if (!$user->activo) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Esta cuenta de usuario ha sido desactivada.',
                ]);
            }

            return $this->redirectBasedOnRol($user);
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Redirige al usuario según su rol.
     */
    protected function redirectBasedOnRol($user)
    {
        if ($user->esAdmin()) {
            return redirect()->intended('/admin/dashboard');
        } elseif ($user->esTecnico()) {
            return redirect()->intended('/tecnico/dashboard');
        } else {
            return redirect()->intended('/cliente/dashboard');
        }
    }
}
