<?php

namespace App\Http\Requests;

use App\Models\Denuncia;
use App\Models\Publicacion;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Ruta anidada esperada: POST /publicaciones/{publicacion}/denuncias.
 */
class StoreDenunciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Denuncia::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'publicacion_id' => $this->route('publicacion') ? ['nullable'] : ['required', 'integer', 'exists:publicaciones,id'],
            'motivo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'publicacion_id.required' => 'La publicación a denunciar es obligatoria.',
            'publicacion_id.exists' => 'La publicación seleccionada no existe.',
            'motivo.required' => 'El motivo es obligatorio.',
            'motivo.max' => 'El motivo no puede superar los 255 caracteres.',
            'descripcion.max' => 'La descripción no puede superar los 2000 caracteres.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Publicacion|null $publicacion */
            $publicacion = $this->getPublicacion();

            if (! $publicacion) {
                return;
            }

            if ($publicacion->usuario_id === $this->user()->id) {
                $validator->errors()->add('publicacion', 'No podés denunciar tu propia publicación.');
            }

            $yaDenuncio = Denuncia::query()
                ->where('usuario_id', $this->user()->id)
                ->where('publicacion_id', $publicacion->id)
                ->exists();

            if ($yaDenuncio) {
                $validator->errors()->add('publicacion', 'Ya denunciaste esta publicación.');
            }
        });
    }

    public function getPublicacion(): ?Publicacion
    {
        $routePublicacion = $this->route('publicacion');

        if ($routePublicacion instanceof Publicacion) {
            return $routePublicacion;
        }

        if (is_numeric($routePublicacion)) {
            return Publicacion::find($routePublicacion);
        }

        return Publicacion::find($this->input('publicacion_id'));
    }
}
