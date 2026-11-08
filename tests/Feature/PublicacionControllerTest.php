<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;

test('los invitados no autenticados son redirigidos al login al intentar crear una publicación', function () {
    $categoria = Categoria::factory()->create();

    $response = $this->post(route('publicaciones.store'), [
        'categoria_id' => $categoria->id,
        'titulo' => 'Tomates frescos',
        'descripcion' => 'Cajón de tomates recién cosechados.',
        'precio' => 1500.50,
        'unidad' => 'cajón',
        'stock' => 10,
    ]);

    $response->assertRedirect(route('login'));
    $this->assertDatabaseEmpty('publicaciones');
});

test('un usuario con rol comprador no puede crear una publicación', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $categoria = Categoria::factory()->create();

    $payload = [
        'categoria_id' => $categoria->id,
        'titulo' => 'Miel pura',
        'descripcion' => 'Frasco de miel pura de monte.',
        'precio' => 2000,
        'unidad' => 'frasco',
        'stock' => 5,
    ];

    $response = $this->actingAs($comprador)->post(route('publicaciones.store'), $payload);

    $response->assertForbidden();
    $this->assertDatabaseMissing('publicaciones', [
        'titulo' => 'Miel pura',
    ]);
});

test('un administrador no puede crear una publicación desde el flujo de publicaciones', function () {
    $admin = User::factory()->admin()->create();
    $categoria = Categoria::factory()->create();

    $payload = [
        'categoria_id' => $categoria->id,
        'titulo' => 'Zapallos',
        'descripcion' => 'Zapallos plomo de primera calidad.',
        'precio' => 800,
        'unidad' => 'kg',
        'stock' => 50,
    ];

    $response = $this->actingAs($admin)->post(route('publicaciones.store'), $payload);

    $response->assertForbidden();
    $this->assertDatabaseMissing('publicaciones', [
        'titulo' => 'Zapallos',
    ]);
});

test('un productor puede crear una publicación con datos válidos', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $payload = [
        'categoria_id' => $categoria->id,
        'titulo' => 'Bananas de Laguna Naineck',
        'descripcion' => 'Bananas locales de excelente maduración y sabor.',
        'precio' => 1200.00,
        'unidad' => 'docena',
        'stock' => 25,
    ];

    $response = $this->actingAs($productor)->post(route('publicaciones.store'), $payload);

    $response->assertSessionHasNoErrors();

    $publicacion = Publicacion::first();

    expect($publicacion)->not->toBeNull();
    $response->assertRedirect(route('publicaciones.show', $publicacion));
    $response->assertSessionHas('success', 'Publicación creada exitosamente.');

    $this->assertDatabaseHas('publicaciones', [
        'id' => $publicacion->id,
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'titulo' => 'Bananas de Laguna Naineck',
        'precio' => 1200.00,
        'unidad' => 'docena',
        'stock' => 25,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
});

test('la creación de publicación valida los campos requeridos', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $response = $this->actingAs($productor)->post(route('publicaciones.store'), []);

    $response->assertSessionHasErrors([
        'categoria_id',
        'titulo',
        'descripcion',
        'precio',
        'unidad',
        'stock',
    ]);
    $this->assertDatabaseEmpty('publicaciones');
});

test('la creación de publicación rechaza precio y stock negativos', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $payload = [
        'categoria_id' => $categoria->id,
        'titulo' => 'Sandías',
        'descripcion' => 'Sandías dulces.',
        'precio' => -500,
        'unidad' => 'kg',
        'stock' => -3,
    ];

    $response = $this->actingAs($productor)->post(route('publicaciones.store'), $payload);

    $response->assertSessionHasErrors([
        'precio' => 'El precio no puede ser negativo.',
        'stock' => 'El stock no puede ser negativo.',
    ]);
    $this->assertDatabaseMissing('publicaciones', [
        'titulo' => 'Sandías',
    ]);
});

test('la creación de publicación rechaza categorías inexistentes', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $payload = [
        'categoria_id' => 99999,
        'titulo' => 'Mandarinas',
        'descripcion' => 'Mandarinas de estación.',
        'precio' => 900,
        'unidad' => 'kg',
        'stock' => 15,
    ];

    $response = $this->actingAs($productor)->post(route('publicaciones.store'), $payload);

    $response->assertSessionHasErrors([
        'categoria_id' => 'La categoría seleccionada no existe.',
    ]);
    $this->assertDatabaseEmpty('publicaciones');
});

test('la creación de publicación rechaza estados inválidos', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $payload = [
        'categoria_id' => $categoria->id,
        'titulo' => 'Pomelos',
        'descripcion' => 'Pomelos rosados.',
        'precio' => 1100,
        'unidad' => 'bolsa',
        'stock' => 8,
        'estado' => 'estado_falso',
    ];

    $response = $this->actingAs($productor)->post(route('publicaciones.store'), $payload);

    $response->assertSessionHasErrors(['estado']);
    $this->assertDatabaseEmpty('publicaciones');
});
