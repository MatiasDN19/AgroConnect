<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoriaController extends Controller
{
    /**
     * Listar todas las categorías disponibles.
     */
    public function index(): AnonymousResourceCollection
    {
        $categorias = Categoria::orderBy('nombre')->get();

        return CategoriaResource::collection($categorias);
    }
}
