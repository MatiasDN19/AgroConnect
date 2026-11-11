<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;

test('los invitados no autenticados son redirigidos al login al consultar el precio sugerido', function () {
    $categoria = Categoria::factory()->create();

    $response = $this->getJson(route('categorias.precio-sugerido', $categoria));

    $response->assertUnauthorized();
});

test('el endpoint devuelve el precio promedio correcto cuando hay suficientes publicaciones activas', function () {
    $usuario = User::factory()->create();
    $categoria = Categoria::factory()->create();

    // 3 publicaciones activas (mínimo representativo requerido)
    Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'precio' => 100.00,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'precio' => 200.00,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'precio' => 300.00,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($usuario)->getJson(route('categorias.precio-sugerido', $categoria));

    $response->assertOk();
    $response->assertJson([
        'precio_promedio' => 200.00,
    ]);
});

test('el endpoint devuelve null cuando hay menos publicaciones activas que el mínimo representativo', function () {
    $usuario = User::factory()->create();
    $categoria = Categoria::factory()->create();

    // Solo 2 publicaciones activas (menor al mínimo de 3)
    Publicacion::factory()->count(2)->create([
        'categoria_id' => $categoria->id,
        'precio' => 150.00,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($usuario)->getJson(route('categorias.precio-sugerido', $categoria));

    $response->assertOk();
    $response->assertJson([
        'precio_promedio' => null,
    ]);
});

test('las publicaciones con estado distinto de activa no entran en el cálculo del precio promedio', function () {
    $usuario = User::factory()->create();
    $categoria = Categoria::factory()->create();

    // 2 activas con promedio 100
    Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'precio' => 80.00,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'precio' => 120.00,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    // 1 pausada y 1 vendida con precios altos que no deben alterar el conteo ni el promedio
    Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'precio' => 5000.00,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'precio' => 9000.00,
        'estado' => Publicacion::ESTADO_VENDIDA,
    ]);

    // Como solo hay 2 activas, no llega al mínimo de 3 y debe devolver null
    $response = $this->actingAs($usuario)->getJson(route('categorias.precio-sugerido', $categoria));

    $response->assertOk();
    $response->assertJson([
        'precio_promedio' => null,
    ]);

    // Si agregamos una tercera activa de 100, ahora son 3 activas: (80 + 120 + 100) / 3 = 100
    Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'precio' => 100.00,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $responseConTres = $this->actingAs($usuario)->getJson(route('categorias.precio-sugerido', $categoria));

    $responseConTres->assertOk();
    $responseConTres->assertJson([
        'precio_promedio' => 100.00,
    ]);
});

test('publicaciones de otras categorías no afectan el promedio de la categoría consultada', function () {
    $usuario = User::factory()->create();
    $categoriaObjetivo = Categoria::factory()->create();
    $otraCategoria = Categoria::factory()->create();

    // 3 activas en categoriaObjetivo: promedio 500
    Publicacion::factory()->count(3)->create([
        'categoria_id' => $categoriaObjetivo->id,
        'precio' => 500.00,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    // Publicaciones en otra categoría
    Publicacion::factory()->count(5)->create([
        'categoria_id' => $otraCategoria->id,
        'precio' => 10.00,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($usuario)->getJson(route('categorias.precio-sugerido', $categoriaObjetivo));

    $response->assertOk();
    $response->assertJson([
        'precio_promedio' => 500.00,
    ]);
});
