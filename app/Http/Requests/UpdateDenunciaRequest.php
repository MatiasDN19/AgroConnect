<?php

namespace App\Http\Requests;

use App\Models\Denuncia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDenunciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Denuncia $denuncia */
        $denuncia = $this->route('denuncia');

        return $this->user()->can('update', $denuncia);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado' => ['required', 'string', Rule::in([
                Denuncia::ESTADO_PENDIENTE,
                Denuncia::ESTADO_REVISADA,
                Denuncia::ESTADO_DESCARTADA,
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

