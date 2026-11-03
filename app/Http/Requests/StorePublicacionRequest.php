<?php

namespace App\Http\Requests;

use App\Models\Publicacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Publicacion::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string', 'max:5000'],
            'precio' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'unidad' => ['required', 'string', 'max:50'],
            'stock' => ['required', 'integer', 'min:0'],
            'estado' => ['sometimes', Rule::in([
                Publicacion::ESTADO_ACTIVA,
                Publicacion::ESTADO_PAUSADA,
                Publicacion::ESTADO_VENDIDA,
            ])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'categoria_id.required' => 'Elegí una categoría para la publicación.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'titulo.required' => 'El título es obligatorio.',
            'precio.min' => 'El precio no puede ser negativo.',
            'stock.min' => 'El stock no puede ser negativo.',
        ];
    }
}
