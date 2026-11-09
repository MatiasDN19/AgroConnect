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

// --- Edición de Publicaciones (KAN-45) ---

test('los invitados no autenticados son redirigidos al login al intentar editar una publicación', function () {
    $publicacion = Publicacion::factory()->create();

    $response = $this->put(route('publicaciones.update', $publicacion), [
        'titulo' => 'Título modificado',
    ]);

    $response->assertRedirect(route('login'));
});

test('el productor dueño puede editar su publicación', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoriaOriginal = Categoria::factory()->create(['nombre' => 'Frutas']);
    $categoriaNueva = Categoria::factory()->create(['nombre' => 'Cítricos']);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoriaOriginal->id,
        'titulo' => 'Naranjas de ombligo',
        'descripcion' => 'Naranjas dulces.',
        'precio' => 1000,
        'unidad' => 'kg',
        'stock' => 20,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $payload = [
        'categoria_id' => $categoriaNueva->id,
        'titulo' => 'Naranjas de ombligo seleccionadas',
        'descripcion' => 'Naranjas dulces recién cosechadas.',
        'precio' => 1250.50,
        'unidad' => 'kg',
        'stock' => 15,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ];

    $response = $this->actingAs($productor)->put(route('publicaciones.update', $publicacion), $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));
    $response->assertSessionHas('success', 'Publicación actualizada exitosamente.');

    $this->assertDatabaseHas('publicaciones', [
        'id' => $publicacion->id,
        'usuario_id' => $productor->id,
        'categoria_id' => $categoriaNueva->id,
        'titulo' => 'Naranjas de ombligo seleccionadas',
        'precio' => 1250.50,
        'stock' => 15,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);
});

test('otro productor no puede editar una publicación ajena', function () {
    $dueno = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $otroProductor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
        'titulo' => 'Título original',
        'precio' => 500,
    ]);

    $response = $this->actingAs($otroProductor)->put(route('publicaciones.update', $publicacion), [
        'titulo' => 'Intento de cambio ajeno',
        'precio' => 100,
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('publicaciones', [
        'id' => $publicacion->id,
        'titulo' => 'Título original',
        'precio' => 500,
    ]);
});

test('un usuario con rol comprador no puede editar una publicación', function () {
    $dueno = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
        'titulo' => 'Título del productor',
    ]);

    $response = $this->actingAs($comprador)->put(route('publicaciones.update', $publicacion), [
        'titulo' => 'Intento de cambio por comprador',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('publicaciones', [
        'id' => $publicacion->id,
        'titulo' => 'Título del productor',
    ]);
});

test('un administrador puede editar cualquier publicación', function () {
    $admin = User::factory()->admin()->create();
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Publicación original',
        'precio' => 900,
    ]);

    $payload = [
        'titulo' => 'Moderado por Admin',
        'precio' => 950,
    ];

    $response = $this->actingAs($admin)->put(route('publicaciones.update', $publicacion), $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));
    $response->assertSessionHas('success', 'Publicación actualizada exitosamente.');

    $this->assertDatabaseHas('publicaciones', [
        'id' => $publicacion->id,
        'usuario_id' => $productor->id,
        'titulo' => 'Moderado por Admin',
        'precio' => 950,
    ]);
});

test('la edición de publicación valida valores negativos en precio y stock', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    $response = $this->actingAs($productor)->put(route('publicaciones.update', $publicacion), [
        'precio' => -20,
        'stock' => -1,
    ]);

    $response->assertSessionHasErrors(['precio', 'stock']);
});
