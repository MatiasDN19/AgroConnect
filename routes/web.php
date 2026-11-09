<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicacionController;
use App\Http\Controllers\ResenaController;
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

    Route::get('/publicaciones', [PublicacionController::class, 'index'])->name('publicaciones.index');

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

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('categorias', CategoriaController::class);
});

require __DIR__.'/auth.php';
