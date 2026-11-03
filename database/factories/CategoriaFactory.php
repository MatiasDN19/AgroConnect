<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categoria>
 */
class CategoriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement(['Verduras', 'Frutas', 'Lácteos', 'Carnes', 'Miel y conservas', 'Huevos']),
            'descripcion' => fake()->sentence(),
        ];
    }
}
