<?php

namespace Database\Factories;

use App\Models\Denuncia;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Denuncia>
 */
class DenunciaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'publicacion_id' => Publicacion::factory(),
            'motivo' => fake()->randomElement(['Producto engañoso', 'Precio irreal', 'Contenido inapropiado', 'Spam']),
            'descripcion' => fake()->optional()->sentence(),
            'estado' => Denuncia::ESTADO_PENDIENTE,
        ];
    }
}
