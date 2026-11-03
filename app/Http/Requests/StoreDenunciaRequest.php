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
            'motivo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Publicacion $publicacion */
            $publicacion = $this->route('publicacion');

            if ($publicacion->usuario_id === $this->user()->id) {
                $validator->errors()->add('publicacion', 'No podés denunciar tu propia publicación.');
            }
        });
    }
}
