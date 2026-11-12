<?php

use App\Models\Publicacion;
use App\Models\Resena;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('los endpoints de resenas requieren autenticacion con token', function () {
    $publicacion = Publicacion::factory()->create();

    $this->getJson("/api/publicaciones/{$publicacion->id}/resenas")->assertUnauthorized();
    $this->postJson("/api/publicaciones/{$publicacion->id}/resenas", [])->assertUnauthorized();
});

test('se pueden listar las resenas de una publicacion via API', function () {
    $usuario = User::factory()->create();
    Sanctum::actingAs($usuario);

    $publicacion = Publicacion::factory()->create();

    $autor1 = User::factory()->create(['name' => 'Autor Uno']);
    $autor2 = User::factory()->create(['name' => 'Autor Dos']);

    $resena1 = Resena::factory()->create([
        'publicacion_id' => $publicacion->id,
        'usuario_id' => $autor1->id,
        'puntuacion' => 5,
        'comentario' => 'Excelente producto',
        'created_at' => now()->subHour(),
    ]);

    $resena2 = Resena::factory()->create([
        'publicacion_id' => $publicacion->id,
        'usuario_id' => $autor2->id,
        'puntuacion' => 4,
        'comentario' => 'Muy bueno',
        'created_at' => now(),
    ]);

    $response = $this->getJson("/api/publicaciones/{$publicacion->id}/resenas");

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('data.0.id', $resena2->id);
    $response->assertJsonPath('data.0.puntuacion', 4);
    $response->assertJsonPath('data.0.usuario.name', 'Autor Dos');
    $response->assertJsonPath('data.1.id', $resena1->id);
    $response->assertJsonPath('data.1.puntuacion', 5);
    $response->assertJsonPath('data.1.usuario.name', 'Autor Uno');
});

test('un usuario autenticado puede crear una resena via API', function () {
    $dueno = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
    ]);

    Sanctum::actingAs($comprador);

    $payload = [
        'puntuacion' => 5,
        'comentario' => 'Tomates de excelente calidad.',
    ];

    $response = $this->postJson("/api/publicaciones/{$publicacion->id}/resenas", $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.puntuacion', 5);
    $response->assertJsonPath('data.comentario', 'Tomates de excelente calidad.');
    $response->assertJsonPath('data.publicacion_id', $publicacion->id);
    $response->assertJsonPath('data.usuario_id', $comprador->id);

    $this->assertDatabaseHas('resenas', [
        'publicacion_id' => $publicacion->id,
        'usuario_id' => $comprador->id,
        'puntuacion' => 5,
        'comentario' => 'Tomates de excelente calidad.',
    ]);
});

test('el dueno de la publicacion no puede resenarse a si mismo via API', function () {
    $dueno = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
    ]);

    Sanctum::actingAs($dueno);

    $payload = [
        'puntuacion' => 5,
        'comentario' => 'Autoelogio',
    ];

    $response = $this->postJson("/api/publicaciones/{$publicacion->id}/resenas", $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['publicacion']);
    $this->assertDatabaseMissing('resenas', [
        'publicacion_id' => $publicacion->id,
        'usuario_id' => $dueno->id,
    ]);
});

test('un usuario no puede crear dos resenas sobre la misma publicacion via API', function () {
    $dueno = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
    ]);

    Resena::factory()->create([
        'publicacion_id' => $publicacion->id,
        'usuario_id' => $comprador->id,
        'puntuacion' => 4,
    ]);

    Sanctum::actingAs($comprador);

    $response = $this->postJson("/api/publicaciones/{$publicacion->id}/resenas", [
        'puntuacion' => 5,
        'comentario' => 'Segunda reseña',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['publicacion']);
});

test('la puntuacion es obligatoria y debe ser entre 1 y 5', function () {
    $dueno = User::factory()->create();
    $comprador = User::factory()->create();
    $publicacion = Publicacion::factory()->create(['usuario_id' => $dueno->id]);

    Sanctum::actingAs($comprador);

    $this->postJson("/api/publicaciones/{$publicacion->id}/resenas", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['puntuacion']);

    $this->postJson("/api/publicaciones/{$publicacion->id}/resenas", ['puntuacion' => 0])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['puntuacion']);

    $this->postJson("/api/publicaciones/{$publicacion->id}/resenas", ['puntuacion' => 6])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['puntuacion']);
});
