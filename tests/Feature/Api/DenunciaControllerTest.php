<?php

use App\Models\Denuncia;
use App\Models\Publicacion;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('los endpoints de denuncias requieren autenticacion con token', function () {
    $publicacion = Publicacion::factory()->create();

    $this->postJson('/api/denuncias', [])->assertUnauthorized();
    $this->postJson("/api/publicaciones/{$publicacion->id}/denuncias", [])->assertUnauthorized();
});

test('un usuario autenticado puede crear una denuncia via POST /api/denuncias', function () {
    $dueno = User::factory()->create();
    $denunciante = User::factory()->create();

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
    ]);

    Sanctum::actingAs($denunciante);

    $payload = [
        'publicacion_id' => $publicacion->id,
        'motivo' => 'Información engañosa sobre el peso',
        'descripcion' => 'Dice que son 10kg pero el cajón tiene 5kg.',
    ];

    $response = $this->postJson('/api/denuncias', $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.publicacion_id', $publicacion->id);
    $response->assertJsonPath('data.usuario_id', $denunciante->id);
    $response->assertJsonPath('data.motivo', 'Información engañosa sobre el peso');
    $response->assertJsonPath('data.estado', Denuncia::ESTADO_PENDIENTE);

    $this->assertDatabaseHas('denuncias', [
        'publicacion_id' => $publicacion->id,
        'usuario_id' => $denunciante->id,
        'motivo' => 'Información engañosa sobre el peso',
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);
});

test('un usuario puede crear una denuncia via ruta anidada POST /api/publicaciones/{id}/denuncias', function () {
    $dueno = User::factory()->create();
    $denunciante = User::factory()->create();

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
    ]);

    Sanctum::actingAs($denunciante);

    $payload = [
        'motivo' => 'Producto en mal estado',
        'descripcion' => 'Las frutas llegaron podridas.',
    ];

    $response = $this->postJson("/api/publicaciones/{$publicacion->id}/denuncias", $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.publicacion_id', $publicacion->id);
    $response->assertJsonPath('data.usuario_id', $denunciante->id);
    $response->assertJsonPath('data.motivo', 'Producto en mal estado');
});

test('el dueno de la publicacion no puede denunciar su propia publicacion via API', function () {
    $dueno = User::factory()->create();
    $publicacion = Publicacion::factory()->create(['usuario_id' => $dueno->id]);

    Sanctum::actingAs($dueno);

    $response = $this->postJson('/api/denuncias', [
        'publicacion_id' => $publicacion->id,
        'motivo' => 'Autodenuncia',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['publicacion']);

    $this->assertDatabaseMissing('denuncias', [
        'publicacion_id' => $publicacion->id,
        'usuario_id' => $dueno->id,
    ]);
});

test('un usuario no puede denunciar dos veces la misma publicacion via API', function () {
    $dueno = User::factory()->create();
    $denunciante = User::factory()->create();
    $publicacion = Publicacion::factory()->create(['usuario_id' => $dueno->id]);

    Denuncia::factory()->create([
        'publicacion_id' => $publicacion->id,
        'usuario_id' => $denunciante->id,
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);

    Sanctum::actingAs($denunciante);

    $response = $this->postJson('/api/denuncias', [
        'publicacion_id' => $publicacion->id,
        'motivo' => 'Segundo intento de denuncia',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['publicacion']);
});

test('crear denuncia requiere motivo y una publicacion valida', function () {
    $usuario = User::factory()->create();
    Sanctum::actingAs($usuario);

    $this->postJson('/api/denuncias', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['motivo', 'publicacion_id']);

    $this->postJson('/api/denuncias', [
        'motivo' => 'Algo',
        'publicacion_id' => 999999,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['publicacion_id']);
});
