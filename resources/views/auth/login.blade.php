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

        <form action="{{ route('login') }}" method="POST" data-loading-form>
            @csrf
            
            <div class="form-group">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="nombre@empresa.com" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required style="padding-right: 2.75rem;">
                    <button type="button" id="togglePassword" aria-label="Mostrar contraseña" title="Mostrar contraseña"
                        style="position: absolute; top: 50%; right: 0.75rem; transform: translateY(-50%); background: none; border: none; padding: 0; cursor: pointer; color: var(--text-muted); font-size: 1rem; line-height: 1;">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;" data-loading-text="Iniciando sesión...">
                <i class="fa-solid fa-right-to-bracket"></i>
                <span>Entrar al Sistema</span>
            </button>
        </form>

        <!-- Hints block for testing -->
        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.06); font-size: 0.8rem; color: var(--text-muted);">
            <p style="font-weight: 700; color: var(--text-main); margin-bottom: 0.65rem;"><i class="fa-solid fa-info-circle"></i> Usuarios de Prueba:</p>
            <ul style="list-style-type: none; padding-left: 0; display: flex; flex-direction: column; gap: 0.4rem;">
                <li style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                    <span><strong style="color: var(--primary);">Administrador:</strong> admin@helpdesk.com</span>
                    <button type="button" class="btn-copy-cred" data-copy="admin@helpdesk.com" data-fill-email="admin@helpdesk.com" title="Copiar correo y rellenar">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </li>
                <li style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                    <span><strong style="color: var(--clr-progreso);">Técnico:</strong> ana.garcia@helpdesk.com</span>
                    <button type="button" class="btn-copy-cred" data-copy="ana.garcia@helpdesk.com" data-fill-email="ana.garcia@helpdesk.com" title="Copiar correo y rellenar">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </li>
                <li style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                    <span><strong style="color: var(--clr-abierto);">Cliente:</strong> roberto@empresa.com</span>
                    <button type="button" class="btn-copy-cred" data-copy="roberto@empresa.com" data-fill-email="roberto@empresa.com" title="Copiar correo y rellenar">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </li>
                <li style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                    <span><strong>Contraseña común:</strong> password</span>
                    <button type="button" class="btn-copy-cred" data-copy="password" data-fill-pass="password" title="Copiar contraseña y rellenar">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('togglePassword');
        const input = document.getElementById('password');
        const emailInput = document.getElementById('email');

        if (toggle && input) {
            toggle.addEventListener('click', function () {
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';

                const icon = toggle.querySelector('i');
                icon.classList.toggle('fa-eye', !isHidden);
                icon.classList.toggle('fa-eye-slash', isHidden);

                const label = isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña';
                toggle.setAttribute('aria-label', label);
                toggle.setAttribute('title', label);
            });
        }

        document.querySelectorAll('.btn-copy-cred').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const text = btn.dataset.copy;
                if (navigator.clipboard && text) {
                    navigator.clipboard.writeText(text).catch(function () {});
                }

                if (btn.dataset.fillEmail && emailInput) {
                    emailInput.value = btn.dataset.fillEmail;
                }
                if (btn.dataset.fillPass && input) {
                    input.value = btn.dataset.fillPass;
                }

                const icon = btn.querySelector('i');
                icon.className = 'fa-solid fa-check';
                btn.classList.add('is-copied');

                setTimeout(function () {
                    icon.className = 'fa-regular fa-copy';
                    btn.classList.remove('is-copied');
                }, 1400);
            });
        });
    });
</script>
@endsection
