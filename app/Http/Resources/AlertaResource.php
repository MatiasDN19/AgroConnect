<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertaResource extends JsonResource
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
            'tipo' => $this->tipo,
            'mensaje' => $this->mensaje,
            'leida' => (bool) $this->leida,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
