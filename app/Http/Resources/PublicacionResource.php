<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicacionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'categoria_id' => $this->categoria_id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,
            'unidad' => $this->unidad,
            'stock' => $this->stock,
            'estado' => $this->estado,
            'distancia' => $this->whenNotNull($this->distancia),
            'puntuacion_promedio' => isset($this->puntuacion_promedio)
                ? (float) $this->puntuacion_promedio
                : ($this->relationLoaded('resenas') ? $this->puntuacionPromedio() : null),
            'usuario' => $this->whenLoaded('usuario', fn () => [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email ?? null,
                'telefono' => $this->usuario->telefono,
                'lat' => $this->usuario->lat,
                'lng' => $this->usuario->lng,
            ]),
            'categoria' => new CategoriaResource($this->whenLoaded('categoria')),
            'resenas' => $this->whenLoaded('resenas'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
