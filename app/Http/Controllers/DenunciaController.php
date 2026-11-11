<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDenunciaRequest;
use App\Http\Requests\UpdateDenunciaRequest;
use App\Models\Denuncia;
use App\Models\Publicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DenunciaController extends Controller
{
    /**
     * Display a listing of complaints for administration.
     */
    public function index(Request $request): Response
    {
        $estado = $request->query('estado', Denuncia::ESTADO_PENDIENTE);

        $denuncias = Denuncia::query()
            ->when($estado, function ($query, $estado) {
                return $query->where('estado', $estado);
            })
            ->with([
                'usuario:id,name,email',
                'publicacion:id,titulo,usuario_id',
                'publicacion.usuario:id,name,email',
            ])
            ->latest()
            ->get();

        return Inertia::render('Admin/Denuncias/Index', [
            'denuncias' => $denuncias,
            'estado_actual' => $estado,
        ]);
    }

    /**
     * Store a newly created complaint in storage.
     */
    public function store(StoreDenunciaRequest $request, Publicacion $publicacion): RedirectResponse
    {
        $publicacion->denuncias()->create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
            'estado' => Denuncia::ESTADO_PENDIENTE,
        ]);

        return redirect()->route('publicaciones.show', $publicacion)
            ->with('success', 'Denuncia enviada exitosamente.');
    }

    /**
     * Update the specified complaint's status in storage.
     */
    public function update(UpdateDenunciaRequest $request, Denuncia $denuncia): RedirectResponse
    {
        $denuncia->update($request->validated());

        return redirect()->route('admin.denuncias.index')
            ->with('success', 'Estado de la denuncia actualizado exitosamente.');
    }
}
