<?php

namespace App\Http\Requests;

use App\Models\Publicacion;
use App\Models\SolicitudCompra;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Ruta anidada esperada: POST /publicaciones/{publicacion}/solicitudes-compra
 */
class StoreSolicitudCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Preparar los datos antes de la validación.
     * Si no se envía cantidad, se asume 1 por defecto (compatibilidad y valor por defecto de base de datos).
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('cantidad')) {
            $this->merge(['cantidad' => 1]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Publicacion|null $publicacion */
        $publicacion = $this->route('publicacion');
        $maxStock = max(1, (int) ($publicacion?->stock ?? 1));

        return [
            'cantidad' => ['required', 'integer', 'min:1', "max:{$maxStock}"],
            'mensaje' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        /** @var Publicacion|null $publicacion */
        $publicacion = $this->route('publicacion');
        $stock = $publicacion?->stock ?? 0;

        return [
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad debe ser al menos 1.',
            'cantidad.max' => "No hay stock suficiente: quedan {$stock}.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Publicacion $publicacion */
            $publicacion = $this->route('publicacion');

            if ($publicacion && $publicacion->usuario_id === $this->user()->id) {
                $validator->errors()->add('publicacion', 'No podés solicitar la compra de tu propia publicación.');
            }

            // Una sola solicitud pendiente por usuario y publicación (KAN-116).
            // Si se la aceptaron o rechazaron, puede volver a solicitar.
            $tienePendiente = $publicacion && SolicitudCompra::query()
                ->where('usuario_id', $this->user()->id)
                ->where('publicacion_id', $publicacion->id)
                ->where('estado', SolicitudCompra::ESTADO_PENDIENTE)
                ->exists();

            if ($tienePendiente) {
                $validator->errors()->add('publicacion', 'Ya tenés una solicitud pendiente para esta publicación.');
            }
        });
    }
}
