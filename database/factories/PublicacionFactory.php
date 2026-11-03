<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publicacion>
 */
class PublicacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'categoria_id' => Categoria::factory(),
            'titulo' => fake()->words(3, true),
            'descripcion' => fake()->paragraph(),
            'precio' => fake()->randomFloat(2, 100, 10000),
            'unidad' => fake()->randomElement(['kg', 'unidad', 'bulto', 'docena']),
            'stock' => fake()->numberBetween(1, 200),
            'estado' => Publicacion::ESTADO_ACTIVA,
        ];
    }

    public function pausada(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => Publicacion::ESTADO_PAUSADA]);
    }

    public function vendida(): static
    {
        return $this->state(fn (array $attributes) => ['estado' => Publicacion::ESTADO_VENDIDA]);
    }
}
