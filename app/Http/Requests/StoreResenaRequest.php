<?php

namespace App\Http\Requests;

use App\Models\Publicacion;
use App\Models\Resena;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Se espera una ruta anidada tipo POST /publicaciones/{publicacion}/resenas,
 * así la publicación viene siempre del route model binding y no de un
 * publicacion_id que el usuario podría manipular a mano.
 */
class StoreResenaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Resena::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'puntuacion' => ['required', 'integer', 'min:1', 'max:5'],
            'comentario' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'puntuacion.required' => 'La puntuación es obligatoria.',
            'puntuacion.min' => 'La puntuación mínima es 1.',
            'puntuacion.max' => 'La puntuación máxima es 5.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Publicacion $publicacion */
            $publicacion = $this->route('publicacion');

            if ($publicacion->usuario_id === $this->user()->id) {
                $validator->errors()->add('publicacion', 'No podés reseñar tu propia publicación.');
            }

            $yaReseno = Resena::query()
                ->where('usuario_id', $this->user()->id)
                ->where('publicacion_id', $publicacion->id)
                ->exists();

            if ($yaReseno) {
                $validator->errors()->add('publicacion', 'Ya dejaste una reseña en esta publicación.');
            }
        });
    }
}
