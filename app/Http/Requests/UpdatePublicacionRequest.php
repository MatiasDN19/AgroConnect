<?php

namespace App\Http\Requests;

use App\Models\Configuracion;
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
            // Límites configurables desde el panel admin (KAN-225).
            'fotos' => ['nullable', 'array', 'max:'.Configuracion::limitesFotos()['maximo']],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.Configuracion::limitesFotos()['tamano_max_kb']],
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
     * Mismos mensajes de fotos que al crear (con los límites configurados, KAN-225).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $limites = Configuracion::limitesFotos();

        return [
            'fotos.max' => "Podés subir como máximo {$limites['maximo']} fotos.",
            'fotos.*.image' => 'Cada archivo debe ser una imagen válida.',
            'fotos.*.mimes' => 'Las fotos deben ser formato JPG, PNG o WEBP.',
            'fotos.*.max' => 'Cada foto no puede superar los '.Configuracion::textoTamano($limites['tamano_max_kb']).'.',
        ];
    }

    /**
     * Configurar el validador para verificar que el total de fotos finales no supere el máximo configurado.
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

            $maximo = Configuracion::limitesFotos()['maximo'];

            if ($totalFinal > $maximo) {
                $validator->errors()->add('fotos', "La publicación no puede tener más de {$maximo} fotos en total.");
            }
        });
    }
}
