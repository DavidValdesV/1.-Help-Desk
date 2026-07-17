<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas protegidas
Route::middleware(['auth'])->group(function () {
    
    // Rutas comunes para visualización y comentarios
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/comentarios', [CommentController::class, 'store'])->name('comments.store');
    Route::post('/tickets/{ticket}/prioridad', [TicketController::class, 'updatePrioridad'])->name('tickets.updatePrioridad');

    // Rutas para clientes
    Route::middleware(['rol:cliente'])->group(function () {
        Route::get('/cliente/dashboard', [TicketController::class, 'index'])->name('cliente.dashboard');
        Route::get('/tickets/nuevo/crear', [TicketController::class, 'create'])->name('tickets.create');
        Route::post('/tickets/nuevo/crear', [TicketController::class, 'store'])->name('tickets.store');
    });

    // Rutas para técnicos
    Route::middleware(['rol:tecnico'])->group(function () {
        Route::get('/tecnico/dashboard', [TicketController::class, 'index'])->name('tecnico.dashboard');
        Route::post('/tickets/{ticket}/estado', [TicketController::class, 'updateEstado'])->name('tickets.updateEstado');
    });

    // Rutas para administradores
    Route::middleware(['rol:admin'])->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/admin/tickets', [TicketController::class, 'index'])->name('admin.tickets');
        Route::post('/tickets/{ticket}/reasignar', [AdminController::class, 'reasignar'])->name('tickets.reasignar');
        Route::post('/admin/tickets/{ticket}/estado', [TicketController::class, 'updateEstado'])->name('admin.tickets.updateEstado');
        Route::get('/admin/auditoria', [AdminController::class, 'auditoria'])->name('admin.auditoria');
    });
});
