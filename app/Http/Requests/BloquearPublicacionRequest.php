<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BloquearPublicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo del bloqueo es obligatorio.',
            'motivo.max' => 'El motivo no puede superar los 1000 caracteres.',
        ];
    }
}
