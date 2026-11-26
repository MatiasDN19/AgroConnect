<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\ReferenciaMensual;
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

// --- KAN-132: comparar por unidad y devolver rango y mediana ---

test('con unidad solo compara publicaciones de esa unidad, sin importar mayúsculas ni espacios', function () {
    $usuario = User::factory()->create();
    $categoria = Categoria::factory()->create();

    foreach ([[900, 'kg'], [1300, 'KG'], [1550, ' kg ']] as [$precio, $unidad]) {
        Publicacion::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => $precio,
            'unidad' => $unidad,
            'estado' => Publicacion::ESTADO_ACTIVA,
        ]);
    }

    // Por cajón: no entra en la referencia por kg.
    Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'precio' => 25000,
        'unidad' => 'cajón',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $this->actingAs($usuario)
        ->getJson(route('categorias.precio-sugerido', ['categoria' => $categoria, 'unidad' => 'Kg']))
        ->assertOk()
        ->assertJson([
            'precio_mediana' => 1300,
            'precio_minimo' => 900,
            'precio_maximo' => 1550,
            'cantidad' => 3,
        ]);
});

test('con unidad devuelve null si no hay suficientes publicaciones de esa unidad', function () {
    $usuario = User::factory()->create();
    $categoria = Categoria::factory()->create();

    Publicacion::factory()->count(3)->create([
        'categoria_id' => $categoria->id,
        'unidad' => 'cajón',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $this->actingAs($usuario)
        ->getJson(route('categorias.precio-sugerido', ['categoria' => $categoria, 'unidad' => 'kg']))
        ->assertOk()
        ->assertJson(['precio_mediana' => null, 'cantidad' => 0]);
});

test('la mediana no se deja arrastrar por un precio fuera de lo común', function () {
    $usuario = User::factory()->create();
    $categoria = Categoria::factory()->create();

    foreach ([900, 1300, 1550, 9999] as $precio) {
        Publicacion::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => $precio,
            'unidad' => 'kg',
            'estado' => Publicacion::ESTADO_ACTIVA,
        ]);
    }

    // Cantidad par: la mediana es el promedio de los dos del medio (1300 y 1550).
    $this->actingAs($usuario)
        ->getJson(route('categorias.precio-sugerido', ['categoria' => $categoria, 'unidad' => 'kg']))
        ->assertOk()
        ->assertJson([
            'precio_promedio' => 3437.25,
            'precio_mediana' => 1425,
            'precio_maximo' => 9999,
            'cantidad' => 4,
        ]);
});

test('precioSugerido incluye las referencias oficiales vigentes del Consorcio para la categoria', function () {
    $usuario = User::factory()->create();
    $catVerduras = Categoria::factory()->create(['nombre' => 'Verduras']);
    $catFrutas = Categoria::factory()->create(['nombre' => 'Frutas']);

    // Referencia vigente mes actual para Verduras
    ReferenciaMensual::create([
        'mes' => now()->startOfMonth()->toDateString(),
        'producto' => 'Zapallo Criollo',
        'categoria_id' => $catVerduras->id,
        'unidad' => 'kg',
        'precio_referencia' => 500.00,
    ]);

    // Referencia de otro mes para Verduras (no debe aparecer)
    ReferenciaMensual::create([
        'mes' => now()->subMonth()->startOfMonth()->toDateString(),
        'producto' => 'Zapallo Viejo',
        'categoria_id' => $catVerduras->id,
        'unidad' => 'kg',
        'precio_referencia' => 300.00,
    ]);

    // Referencia vigente pero de otra categoría (no debe aparecer)
    ReferenciaMensual::create([
        'mes' => now()->startOfMonth()->toDateString(),
        'producto' => 'Banana',
        'categoria_id' => $catFrutas->id,
        'unidad' => 'cajón',
        'precio_referencia' => 4000.00,
    ]);

    $response = $this->actingAs($usuario)
        ->getJson(route('categorias.precio-sugerido', ['categoria' => $catVerduras, 'unidad' => 'kg']));

    $response->assertOk();
    $response->assertJson([
        'referencias' => [
            [
                'producto' => 'Zapallo Criollo',
                'unidad' => 'kg',
                'precio' => 500.00,
                'coincide' => false,
            ],
        ],
    ]);
    expect($response->json('referencias'))->toHaveCount(1);
});

test('precioSugerido marca coincide true cuando el titulo coincide con el producto', function () {
    $usuario = User::factory()->create();
    $categoria = Categoria::factory()->create();

    ReferenciaMensual::create([
        'mes' => now()->startOfMonth()->toDateString(),
        'producto' => 'Tomate Perita',
        'categoria_id' => $categoria->id,
        'unidad' => 'kg',
        'precio_referencia' => 800.00,
    ]);

    $response = $this->actingAs($usuario)
        ->getJson(route('categorias.precio-sugerido', [
            'categoria' => $categoria,
            'unidad' => 'kg',
            'titulo' => 'Cajón de tomates peritas recién cosechados',
        ]));

    $response->assertOk();
    $response->assertJson([
        'referencias' => [
            [
                'producto' => 'Tomate Perita',
                'coincide' => true,
            ],
        ],
    ]);
});
