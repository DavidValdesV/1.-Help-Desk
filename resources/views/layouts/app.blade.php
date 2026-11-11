<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de tickets de soporte TI interno para gestión ágil y auditoría en tiempo real.">
    <title>@yield('title', 'Soporte TI - Help Desk')</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Style System -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @yield('head')
</head>
<body>

    @auth
    <div class="app-container">
        <!-- Mobile Top Bar (visible only on small screens / high zoom) -->
        <header class="mobile-topbar">
            <button type="button" class="menu-toggle" id="menu-toggle" aria-label="Abrir menú" aria-controls="app-sidebar" aria-expanded="false">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="brand brand-mobile">
                <i class="fa-solid fa-headset"></i>
                <span>Help Desk TI</span>
            </div>
            <div class="user-avatar user-avatar-sm" title="{{ auth()->user()->nombre }}">
                {{ strtoupper(substr(auth()->user()->nombre, 0, 1)) }}
            </div>
        </header>

        <!-- Overlay behind the mobile drawer -->
        <div class="sidebar-overlay" id="sidebar-overlay"></div>

        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="app-sidebar">
            <div class="sidebar-header">
                <div class="brand">
                    <i class="fa-solid fa-headset"></i>
                    <span>Help Desk TI</span>
                </div>
                <button type="button" class="menu-close" id="menu-close" aria-label="Cerrar menú">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                @if(auth()->user()->esAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>Panel General</span>
                    </a>
                    <a href="{{ route('admin.tickets') }}" class="nav-link {{ request()->routeIs('admin.tickets') || request()->routeIs('tickets.show') && !request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fa-solid fa-ticket"></i>
                        <span>Todos los Tickets</span>
                    </a>
                @elseif(auth()->user()->esTecnico())
                    <a href="{{ route('tecnico.dashboard') }}" class="nav-link {{ request()->routeIs('tecnico.dashboard') || request()->routeIs('tickets.show') ? 'active' : '' }}">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                        <span>Mis Tickets</span>
                    </a>
                @elseif(auth()->user()->esCliente())
                    <a href="{{ route('cliente.dashboard') }}" class="nav-link {{ request()->routeIs('cliente.dashboard') || request()->routeIs('tickets.show') ? 'active' : '' }}">
                        <i class="fa-solid fa-house"></i>
                        <span>Mis Tickets</span>
                    </a>
                    <a href="{{ route('tickets.create') }}" class="nav-link {{ request()->routeIs('tickets.create') ? 'active' : '' }}">
                        <i class="fa-solid fa-circle-plus"></i>
                        <span>Crear Ticket</span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <div class="user-profile-badge">
                    <div class="user-avatar">
                        {{ strtoupper(substr(auth()->user()->nombre, 0, 1)) }}
                    </div>
                    <div class="user-info">
                        <span class="user-name">{{ auth()->user()->nombre }}</span>
                        <span class="user-role">{{ auth()->user()->rol }}</span>
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-logout">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Grid -->
        <main class="main-content">
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any() && !$__env->yieldContent('suppress_global_errors'))
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <ul class="errors-list">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    @else
        @yield('content')
    @endauth

    <script>
        // Evita envíos duplicados: en formularios con [data-loading-form] el botón
        // se bloquea y muestra un indicador de carga mientras se procesa el envío.
        (function () {
            document.querySelectorAll('form[data-loading-form]').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    if (form.dataset.enviando === '1') {
                        e.preventDefault();
                        return;
                    }
                    form.dataset.enviando = '1';

                    form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                        btn.dataset.originalHtml = btn.innerHTML;
                        const texto = btn.dataset.loadingText || 'Enviando...';
                        btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span><span></span>';
                        btn.lastElementChild.textContent = texto;
                        btn.classList.add('is-loading');
                        btn.setAttribute('aria-busy', 'true');
                        // Se deshabilita en el siguiente ciclo para no cancelar el envío en curso
                        setTimeout(function () { btn.disabled = true; }, 0);
                    });

                    // También se bloquean los demás enlaces/botones del formulario (ej. "Cancelar")
                    form.querySelectorAll('a.btn').forEach(function (a) {
                        a.classList.add('is-disabled');
                        a.setAttribute('aria-disabled', 'true');
                    });
                });
            });

            // Si el usuario vuelve atrás con el navegador, restaurar los botones
            window.addEventListener('pageshow', function (e) {
                if (!e.persisted) return;
                document.querySelectorAll('form[data-loading-form]').forEach(function (form) {
                    form.dataset.enviando = '';
                    form.querySelectorAll('button.is-loading').forEach(function (btn) {
                        btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
                        btn.disabled = false;
                        btn.classList.remove('is-loading');
                        btn.removeAttribute('aria-busy');
                    });
                    form.querySelectorAll('a.btn.is-disabled').forEach(function (a) {
                        a.classList.remove('is-disabled');
                        a.removeAttribute('aria-disabled');
                    });
                });
            });
        })();
    </script>

    @auth
    <script>
        // Menú lateral deslizable para móviles / zoom alto
        (function () {
            const body = document.body;
            const sidebar = document.getElementById('app-sidebar');
            const toggle = document.getElementById('menu-toggle');
            const closeBtn = document.getElementById('menu-close');
            const overlay = document.getElementById('sidebar-overlay');
            if (!sidebar || !toggle) return;

            function abrir() {
                body.classList.add('sidebar-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
            function cerrar() {
                body.classList.remove('sidebar-open');
                toggle.setAttribute('aria-expanded', 'false');
            }

            toggle.addEventListener('click', abrir);
            closeBtn && closeBtn.addEventListener('click', cerrar);
            overlay && overlay.addEventListener('click', cerrar);
            sidebar.querySelectorAll('.nav-link').forEach(l => l.addEventListener('click', cerrar));
            document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrar(); });
            window.addEventListener('resize', () => { if (window.innerWidth > 1024) cerrar(); });
        })();
    </script>
    @endauth

    @yield('scripts')
</body>
</html>
