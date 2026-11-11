<?php

namespace App\Http\Requests;

use App\Models\SolicitudCompra;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveSolicitudCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SolicitudCompra $solicitud */
        $solicitud = $this->route('solicitud');

        return $this->user()->can('resolve', $solicitud);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                'string',
                Rule::in([SolicitudCompra::ESTADO_ACEPTADA, SolicitudCompra::ESTADO_RECHAZADA]),
            ],
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
