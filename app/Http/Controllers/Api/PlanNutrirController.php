<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferenciaMensual;
use Illuminate\Http\JsonResponse;

class PlanNutrirController extends Controller
{
    /**
     * Devuelve la demanda del Plan Nutrir vigente este mes.
     */
    public function demanda(): JsonResponse
    {
        $demanda = ReferenciaMensual::query()
            ->vigentes()
            ->whereNotNull('cantidad_plan_nutrir')
            ->where('cantidad_plan_nutrir', '>', 0)
            ->with('categoria:id,nombre')
            ->orderBy('producto')
            ->get()
            ->map(fn (ReferenciaMensual $ref) => [
                'id' => $ref->id,
                'producto' => $ref->producto,
                'unidad' => $ref->unidad,
                'cantidad' => (float) $ref->cantidad_plan_nutrir,
                'categoria_id' => $ref->categoria_id,
                'categoria' => $ref->categoria ? ['id' => $ref->categoria->id, 'nombre' => $ref->categoria->nombre] : null,
            ]);

        return response()->json([
            'data' => $demanda,
        ]);
    }
}
