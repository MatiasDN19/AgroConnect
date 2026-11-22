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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mensaje' => ['nullable', 'string', 'max:2000'],
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
