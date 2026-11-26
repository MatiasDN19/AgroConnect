<?php

namespace App\Http\Requests\Admin;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReferenciaMensualRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->esAdmin() ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('mes') && $this->input('mes')) {
            // Normalizar el mes al primer día del mes: Y-m-01
            try {
                $normalizado = Carbon::parse($this->input('mes'))->startOfMonth()->toDateString();
                $this->merge(['mes' => $normalizado]);
            } catch (\Exception $e) {
                // Dejar que la regla date lo capture
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $referenciaId = $this->route('referencia')?->id;

        return [
            'mes' => ['required', 'date'],
            'producto' => ['required', 'string', 'max:255'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'unidad' => [
                'required',
                'string',
                'max:50',
                Rule::unique('referencias_mensuales', 'unidad')
                    ->where(fn ($query) => $query
                        ->where('mes', $this->input('mes'))
                        ->where('producto', $this->input('producto'))
                    )
                    ->ignore($referenciaId),
            ],
            'precio_referencia' => ['nullable', 'numeric', 'min:0'],
            'cantidad_plan_nutrir' => ['nullable', 'numeric', 'min:0'],
            'nota' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $precio = $this->input('precio_referencia');
            $cantidad = $this->input('cantidad_plan_nutrir');

            // Al menos uno de los dos tiene que estar presente
            if ((is_null($precio) || $precio === '') && (is_null($cantidad) || $cantidad === '')) {
                $validator->errors()->add(
                    'precio_referencia',
                    'Debe indicar al menos el precio de referencia o la cantidad del Plan Nutrir.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'unidad.unique' => 'Ya existe una referencia para este producto y unidad en el mes indicado.',
        ];
    }
}
