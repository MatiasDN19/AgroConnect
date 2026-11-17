<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    /**
     * Muestra la landing page con estadísticas públicas del marketplace.
     */
    public function index(): Response
    {
        return Inertia::render('Landing', [
            'estadisticas' => [
                'productores' => User::where('rol', User::ROL_PRODUCTOR)->count(),
                'publicaciones_activas' => Publicacion::where('estado', Publicacion::ESTADO_ACTIVA)->count(),
                'categorias' => Categoria::count(),
            ],
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }
}
