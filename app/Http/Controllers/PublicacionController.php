<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambiarEstadoPublicacionRequest;
use App\Http\Requests\StorePublicacionRequest;
use App\Http\Requests\UpdatePublicacionRequest;
use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PublicacionController extends Controller
{
    /**
     * Display a listing of active publications.
     */
    public function index(Request $request): InertiaResponse
    {
        $hasUbicacion = $request->filled('lat') && $request->filled('lng');

        $query = Publicacion::query()
            ->where('publicaciones.estado', Publicacion::ESTADO_ACTIVA)
            ->when($request->filled('categoria_id'), function ($query) use ($request) {
                $query->where('publicaciones.categoria_id', $request->input('categoria_id'));
            })
            ->orderByDesc(
                User::selectRaw('COALESCE(socio_validado, 0)')->whereColumn('users.id', 'publicaciones.usuario_id')
            );

        if ($hasUbicacion) {
            $userLat = (float) $request->input('lat');
            $userLng = (float) $request->input('lng');

            // Join con users para ordenar por distancia usando la fórmula de Haversine
            $cosFormula = "cos(radians({$userLat})) * cos(radians(users.lat)) * cos(radians(users.lng) - radians({$userLng})) + sin(radians({$userLat})) * sin(radians(users.lat))";

            // MySQL/MariaDB usan LEAST/GREATEST para clamping escalar; SQLite usa min/max con 2 argumentos
            $clamped = match (DB::connection()->getDriverName()) {
                'mysql', 'mariadb' => "LEAST(1.0, GREATEST(-1.0, {$cosFormula}))",
                default => "min(1.0, max(-1.0, {$cosFormula}))",
            };

            $haversine = "(6371 * acos({$clamped}))";

            $query->join('users', 'publicaciones.usuario_id', '=', 'users.id')
                ->select('publicaciones.*')
                ->selectRaw("{$haversine} as distancia")
                ->orderByRaw('CASE WHEN users.lat IS NOT NULL AND users.lng IS NOT NULL THEN 0 ELSE 1 END')
                ->orderByRaw("{$haversine} ASC")
                ->orderBy('publicaciones.created_at', 'desc');
        } else {
            $query->latest('publicaciones.created_at');
        }

        $publicaciones = $query
            ->with(['categoria:id,nombre', 'usuario:id,name'])
            ->withAvg('resenas as puntuacion_promedio', 'puntuacion')
            ->get();

        return Inertia::render('Publicaciones/Index', [
            'publicaciones' => $publicaciones,
            'categoria_id' => $request->input('categoria_id'),
            'lat' => $request->input('lat'),
            'lng' => $request->input('lng'),
        ]);
    }

    /**
     * Show the form for creating a new publication.
     * La autorización (solo productores) se aplica en la ruta con ->can('create', Publicacion::class).
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Publicaciones/Create', [
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /**
     * Store a newly created publication in storage.
     */
    public function store(StorePublicacionRequest $request): RedirectResponse
    {
        $publicacion = Publicacion::create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
        ]);

        return redirect()->route('publicaciones.show', $publicacion)
            ->with('success', 'Publicación creada exitosamente.');
    }

    /**
     * Display the specified publication.
     */
    public function show(Publicacion $publicacion): InertiaResponse
    {
        $publicacion->load([
            'usuario:id,name,email,telefono',
            'categoria:id,nombre',
            'resenas.usuario:id,name',
        ]);

        return Inertia::render('Publicaciones/Show', [
            'publicacion' => $publicacion,
            'puntuacion_promedio' => $publicacion->puntuacionPromedio(),
        ]);
    }

    /**
     * Show the form for editing the specified publication.
     * La autorización (dueño o admin) se aplica en la ruta con ->can('update', 'publicacion').
     */
    public function edit(Publicacion $publicacion): InertiaResponse
    {
        return Inertia::render('Publicaciones/Edit', [
            'publicacion' => $publicacion->only([
                'id', 'categoria_id', 'titulo', 'descripcion', 'precio', 'unidad', 'stock',
            ]),
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /**
     * Update the specified publication in storage.
     */
    public function update(UpdatePublicacionRequest $request, Publicacion $publicacion): RedirectResponse
    {
        $publicacion->update($request->validated());

        return redirect()->route('publicaciones.show', $publicacion)
            ->with('success', 'Publicación actualizada exitosamente.');
    }

    /**
     * Change the publication's state (activa, pausada, vendida).
     */
    public function cambiarEstado(CambiarEstadoPublicacionRequest $request, Publicacion $publicacion): RedirectResponse
    {
        $publicacion->update($request->validated());

        return back(fallback: route('publicaciones.show', $publicacion))
            ->with('success', 'Estado de la publicación actualizado exitosamente.');
    }

    /**
     * Devuelve el precio promedio sugerido para una categoría basado en publicaciones activas.
     */
    public function precioSugerido(Categoria $categoria): JsonResponse
    {
        $precioPromedio = Publicacion::calcularPrecioPromedioPorCategoria($categoria->id);

        return response()->json([
            'precio_promedio' => $precioPromedio,
        ]);
    }

    /**
     * Devuelve las publicaciones activas georreferenciadas (cuyo dueño tiene lat y lng no nulos) para el mapa.
     */
    public function mapa(): JsonResponse
    {
        $publicaciones = Publicacion::query()
            ->where('estado', Publicacion::ESTADO_ACTIVA)
            ->whereHas('usuario', function ($query) {
                $query->whereNotNull('lat')->whereNotNull('lng');
            })
            ->with('usuario:id,lat,lng')
            ->get(['id', 'usuario_id', 'titulo', 'precio', 'unidad'])
            ->map(function (Publicacion $publicacion) {
                return [
                    'id' => $publicacion->id,
                    'titulo' => $publicacion->titulo,
                    'precio' => $publicacion->precio,
                    'unidad' => $publicacion->unidad,
                    'lat' => (float) $publicacion->usuario->lat,
                    'lng' => (float) $publicacion->usuario->lng,
                ];
            });

        return response()->json($publicaciones);
    }
}
