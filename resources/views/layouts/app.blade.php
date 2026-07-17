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
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('head')
</head>
<body>

    @auth
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="brand">
                <i class="fa-solid fa-headset"></i>
                <span>Help Desk TI</span>
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

            @if($errors->any())
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

    @yield('scripts')
</body>
</html>
