<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DenunciaController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicacionController;
use App\Http\Controllers\ResenaController;
use App\Http\Controllers\SolicitudCompraController;
use App\Models\Publicacion;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/categorias/disponibles', [CategoriaController::class, 'listado'])->name('categorias.disponibles');
    Route::get('/categorias/{categoria}/precio-sugerido', [PublicacionController::class, 'precioSugerido'])->name('categorias.precio-sugerido');

    Route::get('/publicaciones', [PublicacionController::class, 'index'])->name('publicaciones.index');
    Route::get('/publicaciones/mapa', [PublicacionController::class, 'mapa'])->name('publicaciones.mapa');

    // Tiene que ir antes de /publicaciones/{publicacion}, si no "crear" se toma como id.
    Route::get('/publicaciones/crear', [PublicacionController::class, 'create'])
        ->can('create', Publicacion::class)
        ->name('publicaciones.create');
    Route::post('/publicaciones', [PublicacionController::class, 'store'])->name('publicaciones.store');
    Route::get('/publicaciones/{publicacion}', [PublicacionController::class, 'show'])
        ->can('view', 'publicacion')
        ->name('publicaciones.show');
    Route::get('/publicaciones/{publicacion}/editar', [PublicacionController::class, 'edit'])
        ->can('update', 'publicacion')
        ->name('publicaciones.edit');
    Route::match(['put', 'patch'], '/publicaciones/{publicacion}', [PublicacionController::class, 'update'])->name('publicaciones.update');
    Route::patch('/publicaciones/{publicacion}/estado', [PublicacionController::class, 'cambiarEstado'])->name('publicaciones.estado');
    Route::post('/publicaciones/{publicacion}/resenas', [ResenaController::class, 'store'])->name('publicaciones.resenas.store');
    Route::post('/publicaciones/{publicacion}/denuncias', [DenunciaController::class, 'store'])->name('publicaciones.denuncias.store');

    Route::get('/favoritos', [FavoritoController::class, 'index'])->name('favoritos.index');
    Route::post('/publicaciones/{publicacion}/favorito', [FavoritoController::class, 'store'])->name('favoritos.store');
    Route::delete('/publicaciones/{publicacion}/favorito', [FavoritoController::class, 'destroy'])->name('favoritos.destroy');

    Route::post('/publicaciones/{publicacion}/solicitudes-compra', [SolicitudCompraController::class, 'store'])->name('solicitudes-compra.store');
    Route::get('/solicitudes-compra', [SolicitudCompraController::class, 'index'])->name('solicitudes-compra.index');
    Route::patch('/solicitudes-compra/{solicitud}', [SolicitudCompraController::class, 'resolve'])->name('solicitudes-compra.resolve');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/perfil/socio', [ProfileController::class, 'updateSocio'])->name('profile.socio.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('categorias', CategoriaController::class);
    Route::get('/admin/denuncias', [DenunciaController::class, 'index'])->name('admin.denuncias.index');
    Route::patch('/admin/denuncias/{denuncia}', [DenunciaController::class, 'update'])->name('admin.denuncias.update');
});

require __DIR__.'/auth.php';
