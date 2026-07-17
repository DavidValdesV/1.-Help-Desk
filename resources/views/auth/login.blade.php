@extends('layouts.app')

@section('title', 'Iniciar Sesión - Mesa de Soporte TI')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-brand">
                <i class="fa-solid fa-headset"></i>
                <span>Help Desk TI</span>
            </div>
            <p>Ingresa a tu cuenta de soporte interno</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span style="font-size: 0.85rem;">{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="nombre@empresa.com" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="form-group" style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;">
                <label class="form-check">
                    <input type="checkbox" name="remember" class="form-check-input">
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Recordarme</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span>Entrar al Sistema</span>
            </button>
        </form>

        <!-- Hints block for testing -->
        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.06); font-size: 0.8rem; color: var(--text-muted);">
            <p style="font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;"><i class="fa-solid fa-info-circle"></i> Usuarios de Prueba:</p>
            <ul style="list-style-type: none; padding-left: 0;">
                <li style="margin-bottom: 0.25rem;"><strong style="color: var(--primary);">Administrador:</strong> admin@helpdesk.com</li>
                <li style="margin-bottom: 0.25rem;"><strong style="color: var(--clr-progreso);">Técnico:</strong> ana.garcia@helpdesk.com</li>
                <li style="margin-bottom: 0.25rem;"><strong style="color: var(--clr-abierto);">Cliente:</strong> roberto@empresa.com</li>
                <li><strong>Contraseña común:</strong> password</li>
            </ul>
        </div>
    </div>
</div>
@endsection
