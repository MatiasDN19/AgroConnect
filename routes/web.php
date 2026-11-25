<?php

use App\Http\Controllers\Admin\ConfiguracionController;
use App\Http\Controllers\Admin\MetricasController;
use App\Http\Controllers\Admin\PublicacionModeracionController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\AlertaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DenunciaController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicacionController;
use App\Http\Controllers\ResenaController;
use App\Http\Controllers\SocioController;
use App\Http\Controllers\SolicitudCompraController;
use App\Models\Publicacion;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::middleware('auth')->group(function () {
    Route::get('/categorias/disponibles', [CategoriaController::class, 'listado'])->name('categorias.disponibles');
    Route::get('/categorias/{categoria}/precio-sugerido', [PublicacionController::class, 'precioSugerido'])->name('categorias.precio-sugerido');

    Route::get('/publicaciones', [PublicacionController::class, 'index'])->name('publicaciones.index');
    Route::get('/publicaciones/sugeridas', [PublicacionController::class, 'sugeridas'])->name('publicaciones.sugeridas');
    Route::get('/publicaciones/mapa', [PublicacionController::class, 'mapa'])->name('publicaciones.mapa');

    Route::get('/mis-publicaciones', [PublicacionController::class, 'misPublicaciones'])
        ->can('create', Publicacion::class)
        ->name('publicaciones.mias');
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

    Route::get('/alertas', [AlertaController::class, 'index'])->name('alertas.index');
    Route::patch('/alertas/{alerta}/leida', [AlertaController::class, 'marcarLeida'])->name('alertas.marcar-leida');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/perfil/socio', [ProfileController::class, 'updateSocio'])->name('profile.socio.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/resumen', [MetricasController::class, 'index'])->name('admin.resumen');
    Route::get('/admin/metricas', [MetricasController::class, 'index'])->name('admin.metricas.index');
    Route::resource('categorias', CategoriaController::class);
    Route::get('/admin/denuncias', [DenunciaController::class, 'index'])->name('admin.denuncias.index');
    Route::patch('/admin/denuncias/{denuncia}', [DenunciaController::class, 'update'])->name('admin.denuncias.update');
    Route::get('/admin/usuarios', [UsuarioController::class, 'index'])->name('admin.usuarios.index');
    Route::patch('/admin/usuarios/{usuario}/inhabilitar', [UsuarioController::class, 'toggleInhabilitado'])->name('admin.usuarios.inhabilitar');
    Route::patch('/admin/usuarios/{usuario}/rol', [UsuarioController::class, 'cambiarRol'])->name('admin.usuarios.rol');
    Route::get('/admin/socios', [SocioController::class, 'index'])->name('admin.socios.index');
    Route::match(['post', 'patch'], '/admin/socios/{usuario}/aceptar', [SocioController::class, 'aceptar'])->name('admin.socios.aceptar');
    Route::match(['post', 'patch'], '/admin/socios/{usuario}/rechazar', [SocioController::class, 'rechazar'])->name('admin.socios.rechazar');
    Route::get('/admin/configuracion', [ConfiguracionController::class, 'edit'])->name('admin.configuracion.edit');
    Route::put('/admin/configuracion', [ConfiguracionController::class, 'update'])->name('admin.configuracion.update');
    Route::get('/admin/publicaciones', [PublicacionModeracionController::class, 'index'])->name('admin.publicaciones.index');
    Route::patch('/admin/publicaciones/{publicacion}/bloquear', [PublicacionModeracionController::class, 'bloquear'])->name('admin.publicaciones.bloquear');
    Route::patch('/admin/publicaciones/{publicacion}/desbloquear', [PublicacionModeracionController::class, 'desbloquear'])->name('admin.publicaciones.desbloquear');
    Route::delete('/admin/publicaciones/{publicacion}', [PublicacionModeracionController::class, 'destroy'])->name('admin.publicaciones.destroy');
});

require __DIR__.'/auth.php';
