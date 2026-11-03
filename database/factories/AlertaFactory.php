<?php

namespace Database\Factories;

use App\Models\Alerta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alerta>
 */
class AlertaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'tipo' => fake()->randomElement(['denuncia', 'resena', 'sistema']),
            'mensaje' => fake()->sentence(),
            'leida' => false,
        ];
    }
}
