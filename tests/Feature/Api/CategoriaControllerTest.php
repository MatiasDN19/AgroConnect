<?php

use App\Models\Categoria;

test('el endpoint de categorias es publico y devuelve las categorias ordenadas alfabeticamente', function () {
    $catZanahoria = Categoria::factory()->create(['nombre' => 'Zanahorias', 'descripcion' => 'Hortalizas']);
    $catBanana = Categoria::factory()->create(['nombre' => 'Bananas', 'descripcion' => 'Frutas subtropicales']);
    $catAcelga = Categoria::factory()->create(['nombre' => 'Acelgas', 'descripcion' => 'Verduras de hoja']);

    $response = $this->getJson('/api/categorias');

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'nombre',
                'descripcion',
                'created_at',
                'updated_at',
            ],
        ],
    ]);

    $data = $response->json('data');
    expect($data)->toHaveCount(3);
    expect($data[0]['nombre'])->toBe('Acelgas');
    expect($data[1]['nombre'])->toBe('Bananas');
    expect($data[2]['nombre'])->toBe('Zanahorias');
});

test('el endpoint de categorias devuelve una lista vacia si no existen categorias', function () {
    $response = $this->getJson('/api/categorias');

    $response->assertOk();
    $response->assertJson([
        'data' => [],
    ]);
});
