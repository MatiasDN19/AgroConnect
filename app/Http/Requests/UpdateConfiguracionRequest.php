<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionRequest extends FormRequest
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
            'contacto_email' => ['nullable', 'email', 'max:255'],
            'contacto_whatsapp' => ['nullable', 'string', 'max:50'],
            'contacto_direccion' => ['nullable', 'string', 'max:255'],
            'red_facebook' => ['nullable', 'url', 'max:255'],
            'red_instagram' => ['nullable', 'url', 'max:255'],
            'precio_sugerido_minimo' => ['nullable', 'integer', 'min:1', 'max:100'],
            'fotos_maximo' => ['nullable', 'integer', 'min:1', 'max:10'],
            'fotos_tamano_max_kb' => ['nullable', 'integer', 'min:512', 'max:10240'],
        ];
    }

    /**
     * Nombres legibles para los mensajes de error ("El campo Instagram debe ser una URL
     * válida." en lugar de "El campo red instagram…"). KAN-225.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contacto_email' => 'email de contacto',
            'contacto_whatsapp' => 'WhatsApp de contacto',
            'contacto_direccion' => 'dirección',
            'red_facebook' => 'Facebook',
            'red_instagram' => 'Instagram',
            'precio_sugerido_minimo' => 'publicaciones mínimas para el precio de referencia',
            'fotos_maximo' => 'fotos por publicación',
            'fotos_tamano_max_kb' => 'tamaño máximo por foto',
        ];
    }
}
