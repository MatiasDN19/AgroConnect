<?php

use App\Http\Controllers\Api\AlertaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\DenunciaController;
use App\Http\Controllers\Api\PublicacionController;
use App\Http\Controllers\Api\ResenaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/categorias', [CategoriaController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/publicaciones', [PublicacionController::class, 'index']);
    Route::get('/publicaciones/{publicacion}', [PublicacionController::class, 'show']);
    Route::post('/publicaciones', [PublicacionController::class, 'store']);
    Route::put('/publicaciones/{publicacion}', [PublicacionController::class, 'update']);
    Route::patch('/publicaciones/{publicacion}/estado', [PublicacionController::class, 'cambiarEstado']);

    Route::get('/publicaciones/{publicacion}/resenas', [ResenaController::class, 'index']);
    Route::post('/publicaciones/{publicacion}/resenas', [ResenaController::class, 'store']);

    Route::post('/denuncias', [DenunciaController::class, 'store']);
    Route::post('/publicaciones/{publicacion}/denuncias', [DenunciaController::class, 'store']);

    Route::get('/alertas', [AlertaController::class, 'index']);
    Route::patch('/alertas/{alerta}/leida', [AlertaController::class, 'marcarLeida']);
});
