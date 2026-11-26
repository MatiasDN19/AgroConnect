<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReferenciaMensualRequest;
use App\Models\Categoria;
use App\Models\ReferenciaMensual;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReferenciaMensualController extends Controller
{
    /**
     * Display a listing of monthly references.
     */
    public function index(Request $request): InertiaResponse
    {
        $mesInput = $request->input('mes');

        try {
            $fecha = $mesInput ? Carbon::parse($mesInput)->startOfMonth() : now()->startOfMonth();
        } catch (\Exception $e) {
            $fecha = now()->startOfMonth();
        }

        $mes = $fecha->toDateString(); // Y-m-01

        $referencias = ReferenciaMensual::query()
            ->delMes($mes)
            ->with('categoria:id,nombre')
            ->orderBy('producto')
            ->get();

        $categorias = Categoria::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Admin/Referencias/Index', [
            'referencias' => $referencias,
            'categorias' => $categorias,
            'mes' => $mes,
        ]);
    }

    /**
     * Store a newly created monthly reference in storage.
     */
    public function store(ReferenciaMensualRequest $request): RedirectResponse
    {
        $referencia = ReferenciaMensual::create($request->validated());

        return redirect()->back()->with('success', 'Referencia del mes agregada exitosamente.');
    }

    /**
     * Update the specified monthly reference in storage.
     */
    public function update(ReferenciaMensualRequest $request, ReferenciaMensual $referencia): RedirectResponse
    {
        $referencia->update($request->validated());

        return redirect()->back()->with('success', 'Referencia actualizada exitosamente.');
    }

    /**
     * Remove the specified monthly reference from storage.
     */
    public function destroy(ReferenciaMensual $referencia): RedirectResponse
    {
        $referencia->delete();

        return redirect()->back()->with('success', 'Referencia eliminada exitosamente.');
    }

    /**
     * Copy references from the previous month to the current selected month.
     */
    public function copiarMesAnterior(Request $request): RedirectResponse
    {
        $request->validate([
            'mes' => ['required', 'date'],
        ]);

        $fechaDestino = Carbon::parse($request->input('mes'))->startOfMonth();
        $mesDestino = $fechaDestino->toDateString();

        $fechaOrigen = (clone $fechaDestino)->subMonth()->startOfMonth();
        $mesOrigen = $fechaOrigen->toDateString();

        $referenciasOrigen = ReferenciaMensual::where('mes', $mesOrigen)->get();

        if ($referenciasOrigen->isEmpty()) {
            return redirect()->back()->with('info', 'No hay referencias en el mes anterior para copiar.');
        }

        $copiadas = 0;
        foreach ($referenciasOrigen as $origen) {
            // Verificar si ya existe en el mes destino
            $existe = ReferenciaMensual::where('mes', $mesDestino)
                ->where('producto', $origen->producto)
                ->where('unidad', $origen->unidad)
                ->exists();

            if (! $existe) {
                ReferenciaMensual::create([
                    'mes' => $mesDestino,
                    'producto' => $origen->producto,
                    'categoria_id' => $origen->categoria_id,
                    'unidad' => $origen->unidad,
                    'precio_referencia' => $origen->precio_referencia,
                    'cantidad_plan_nutrir' => $origen->cantidad_plan_nutrir,
                    'nota' => $origen->nota,
                ]);
                $copiadas++;
            }
        }

        return redirect()->back()->with(
            'success',
            "Se copiaron {$copiadas} referencias del mes anterior sin duplicar."
        );
    }
}
