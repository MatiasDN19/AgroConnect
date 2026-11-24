<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateConfiguracionRequest;
use App\Models\Configuracion;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController extends Controller
{
    /**
     * Mostrar la pantalla de edición de configuraciones de la plataforma.
     */
    public function edit(): Response
    {
        $claves = array_keys(config('agroconnect', []));
        $configuraciones = [];

        foreach ($claves as $clave) {
            $configuraciones[$clave] = Configuracion::obtener($clave);
        }

        return Inertia::render('Admin/Configuracion/Edit', [
            'configuraciones' => $configuraciones,
        ]);
    }

    /**
     * Guardar los cambios de configuración.
     */
    public function update(UpdateConfiguracionRequest $request): RedirectResponse
    {
        foreach ($request->validated() as $clave => $valor) {
            Configuracion::fijar($clave, $valor);
        }

        return redirect()->route('admin.configuracion.edit')
            ->with('success', 'Configuración actualizada exitosamente.');
    }
}
