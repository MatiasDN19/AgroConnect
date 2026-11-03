<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Pensado solo para desarrollo local: un admin, un productor de prueba
     * y las categorías base del marketplace. Nada de esto corre en producción
     * a menos que se llame explícitamente.
     */
    public function run(): void
    {
        $categorias = [
            'Verduras',
            'Frutas',
            'Lácteos',
            'Carnes',
            'Miel y conservas',
            'Huevos',
        ];

        foreach ($categorias as $nombre) {
            Categoria::query()->firstOrCreate(['nombre' => $nombre]);
        }

        User::factory()->admin()->create([
            'name' => 'Admin Consorcio',
            'email' => 'admin@agroconnect.test',
        ]);

        User::factory()->create([
            'name' => 'Productor de Prueba',
            'email' => 'productor@agroconnect.test',
        ]);
    }
}
