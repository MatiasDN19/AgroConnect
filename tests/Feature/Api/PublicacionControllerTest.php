<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('los endpoints de publicaciones requieren autenticacion con token', function () {
    $publicacion = Publicacion::factory()->create();

    $this->getJson('/api/publicaciones')->assertUnauthorized();
    $this->getJson("/api/publicaciones/{$publicacion->id}")->assertUnauthorized();
    $this->postJson('/api/publicaciones', [])->assertUnauthorized();
    $this->putJson("/api/publicaciones/{$publicacion->id}", [])->assertUnauthorized();
    $this->patchJson("/api/publicaciones/{$publicacion->id}/estado", [])->assertUnauthorized();
});

test('el listado de publicaciones solo devuelve publicaciones activas', function () {
    $usuario = User::factory()->create();
    Sanctum::actingAs($usuario);

    $activa = Publicacion::factory()->create([
        'titulo' => 'Tomates frescos',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $pausada = Publicacion::factory()->create([
        'titulo' => 'Papas pausadas',
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    $vendida = Publicacion::factory()->create([
        'titulo' => 'Lechugas agotadas',
        'estado' => Publicacion::ESTADO_VENDIDA,
    ]);

    $response = $this->getJson('/api/publicaciones');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $activa->id);
    $response->assertJsonPath('data.0.titulo', 'Tomates frescos');
});

test('el listado de publicaciones puede filtrarse por categoria', function () {
    $usuario = User::factory()->create();
    Sanctum::actingAs($usuario);

    $catVerduras = Categoria::factory()->create(['nombre' => 'Verduras']);
    $catFrutas = Categoria::factory()->create(['nombre' => 'Frutas']);

    $pubVerdura = Publicacion::factory()->create([
        'categoria_id' => $catVerduras->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $pubFruta = Publicacion::factory()->create([
        'categoria_id' => $catFrutas->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->getJson("/api/publicaciones?categoria_id={$catVerduras->id}");

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $pubVerdura->id);
    $response->assertJsonPath('data.0.categoria_id', $catVerduras->id);
});

test('un usuario puede ver el detalle de una publicacion', function () {
    $usuario = User::factory()->create();
    Sanctum::actingAs($usuario);

    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'name' => 'Don Pedro',
        'telefono' => '3718112233',
    ]);

    $categoria = Categoria::factory()->create(['nombre' => 'Citricos']);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'titulo' => 'Pomelos rosados',
        'descripcion' => 'Cosechados en Laguna Naineck.',
        'precio' => 1200.50,
        'unidad' => 'cajón',
        'stock' => 15,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->getJson("/api/publicaciones/{$publicacion->id}");

    $response->assertOk();
    $response->assertJsonPath('data.id', $publicacion->id);
    $response->assertJsonPath('data.titulo', 'Pomelos rosados');
    $response->assertJsonPath('data.precio', '1200.50');
    $response->assertJsonPath('data.unidad', 'cajón');
    $response->assertJsonPath('data.categoria.nombre', 'Citricos');
    $response->assertJsonPath('data.usuario.name', 'Don Pedro');
    $response->assertJsonPath('data.usuario.telefono', '3718112233');
});

test('un productor puede crear una publicacion via API', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    Sanctum::actingAs($productor);

    $categoria = Categoria::factory()->create();

    $payload = [
        'categoria_id' => $categoria->id,
        'titulo' => 'Miel pura de monte',
        'descripcion' => 'Frasco de miel 1kg',
        'precio' => 2500,
        'unidad' => 'frasco',
        'stock' => 20,
    ];

    $response = $this->postJson('/api/publicaciones', $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.titulo', 'Miel pura de monte');
    $response->assertJsonPath('data.precio', '2500.00');
    $response->assertJsonPath('data.usuario_id', $productor->id);

    $this->assertDatabaseHas('publicaciones', [
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'titulo' => 'Miel pura de monte',
        'stock' => 20,
    ]);
});

test('un comprador no puede crear una publicacion via API y recibe 403', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    Sanctum::actingAs($comprador);

    $categoria = Categoria::factory()->create();

    $payload = [
        'categoria_id' => $categoria->id,
        'titulo' => 'Intento Comprador',
        'descripcion' => 'No debería permitirse',
        'precio' => 1000,
        'unidad' => 'kg',
        'stock' => 5,
    ];

    $response = $this->postJson('/api/publicaciones', $payload);

    $response->assertForbidden();
    $this->assertDatabaseMissing('publicaciones', [
        'titulo' => 'Intento Comprador',
    ]);
});

test('el dueno de la publicacion puede editarla via API', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    Sanctum::actingAs($productor);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Titulo Original',
        'precio' => 1000,
    ]);

    $payload = [
        'titulo' => 'Titulo Actualizado',
        'precio' => 1500,
    ];

    $response = $this->putJson("/api/publicaciones/{$publicacion->id}", $payload);

    $response->assertOk();
    $response->assertJsonPath('data.titulo', 'Titulo Actualizado');
    $response->assertJsonPath('data.precio', '1500.00');

    $this->assertDatabaseHas('publicaciones', [
        'id' => $publicacion->id,
        'titulo' => 'Titulo Actualizado',
        'precio' => 1500,
    ]);
});

test('un usuario que no es el dueno recibe 403 al intentar editar una publicacion', function () {
    $dueno = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $otroUsuario = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
        'titulo' => 'Titulo Original',
    ]);

    Sanctum::actingAs($otroUsuario);

    $response = $this->putJson("/api/publicaciones/{$publicacion->id}", [
        'titulo' => 'Intento Hacker',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('publicaciones', [
        'titulo' => 'Intento Hacker',
    ]);
});

test('el dueno puede cambiar el estado de su publicacion via API', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    Sanctum::actingAs($productor);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->patchJson("/api/publicaciones/{$publicacion->id}/estado", [
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.estado', Publicacion::ESTADO_PAUSADA);

    $this->assertDatabaseHas('publicaciones', [
        'id' => $publicacion->id,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);
});

test('cambiar estado con un valor invalido devuelve 422', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    Sanctum::actingAs($productor);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
    ]);

    $response = $this->patchJson("/api/publicaciones/{$publicacion->id}/estado", [
        'estado' => 'estado_inventado',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['estado']);
});
