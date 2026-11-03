<?php

namespace App\Http\Requests;

use App\Models\Publicacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Publicacion $publicacion */
        $publicacion = $this->route('publicacion');

        return $this->user()->can('update', $publicacion);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'categoria_id' => ['sometimes', 'required', 'integer', 'exists:categorias,id'],
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['sometimes', 'required', 'string', 'max:5000'],
            'precio' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999999.99'],
            'unidad' => ['sometimes', 'required', 'string', 'max:50'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'estado' => ['sometimes', 'required', Rule::in([
                Publicacion::ESTADO_ACTIVA,
                Publicacion::ESTADO_PAUSADA,
                Publicacion::ESTADO_VENDIDA,
            ])],
        ];
    }
}
