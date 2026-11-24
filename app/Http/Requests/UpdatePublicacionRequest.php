<?php

namespace App\Http\Requests;

use App\Models\Publicacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePublicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Publicacion $publicacion */
        $publicacion = $this->route('publicacion');

        // Si la publicación está bloqueada por un administrador, el dueño no puede modificarla
        if ($publicacion && $publicacion->estaBloqueada()) {
            return false;
        }

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
            'fotos' => ['nullable', 'array', 'max:6'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'fotos_eliminar' => ['nullable', 'array'],
            'fotos_eliminar.*' => ['integer', 'exists:publicacion_fotos,id'],
            'estado' => ['sometimes', 'required', Rule::in([
                Publicacion::ESTADO_ACTIVA,
                Publicacion::ESTADO_PAUSADA,
                Publicacion::ESTADO_VENDIDA,
            ])],
        ];
    }

    /**
     * Configurar el validador para verificar que el total de fotos finales no supere 6.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            /** @var Publicacion $publicacion */
            $publicacion = $this->route('publicacion');

            if (! $publicacion) {
                return;
            }

            $fotosActuales = $publicacion->fotos()->count();
            $fotosAEliminar = count($this->input('fotos_eliminar', []));
            $fotosNuevas = count($this->file('fotos', []));

            $totalFinal = ($fotosActuales - $fotosAEliminar) + $fotosNuevas;

            if ($totalFinal > 6) {
                $validator->errors()->add('fotos', 'La publicación no puede tener más de 6 fotos en total.');
            }
        });
    }
}
