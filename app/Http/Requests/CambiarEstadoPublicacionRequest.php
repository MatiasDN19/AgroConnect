<?php

namespace App\Http\Requests;

use App\Models\Publicacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoPublicacionRequest extends FormRequest
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
            'estado' => ['required', 'string', Rule::in([
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
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ];
    }
}
