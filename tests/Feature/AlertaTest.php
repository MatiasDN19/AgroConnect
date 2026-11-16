<?php

use App\Models\Alerta;
use App\Models\Denuncia;
use App\Models\Publicacion;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('al crear una resena via web se genera una alerta para el dueno de la publicacion', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Tomates Perita',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this
        ->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), [
            'puntuacion' => 5,
            'comentario' => 'Excelente calidad de tomates.',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));

    $this->assertDatabaseHas('alertas', [
        'usuario_id' => $productor->id,
        'tipo' => 'nueva_resena',
        'mensaje' => "Tu publicación 'Tomates Perita' recibió una nueva reseña.",
        'leida' => false,
    ]);

    $alerta = Alerta::where('usuario_id', $productor->id)->first();
    expect($alerta)->not->toBeNull();
    expect($alerta->mensaje)->not->toBeEmpty();
    expect($alerta->leida)->toBeFalse();
});

test('al crear una resena via api se genera una alerta para el dueno de la publicacion', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Sandías Dulces',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    Sanctum::actingAs($comprador);

    $response = $this->postJson("/api/publicaciones/{$publicacion->id}/resenas", [
        'puntuacion' => 4,
        'comentario' => 'Muy ricas sandías.',
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('alertas', [
        'usuario_id' => $productor->id,
        'tipo' => 'nueva_resena',
        'mensaje' => "Tu publicación 'Sandías Dulces' recibió una nueva reseña.",
        'leida' => false,
    ]);
});

test('al resolver una denuncia como revisada se generan alertas para el denunciante y el productor', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $denunciante = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Naranjas de Laguna Naineck',
    ]);

    $denuncia = Denuncia::factory()->create([
        'usuario_id' => $denunciante->id,
        'publicacion_id' => $publicacion->id,
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('admin.denuncias.update', $denuncia), [
            'estado' => Denuncia::ESTADO_REVISADA,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.denuncias.index'));

    // Alerta al denunciante
    $this->assertDatabaseHas('alertas', [
        'usuario_id' => $denunciante->id,
        'tipo' => 'denuncia_resuelta',
        'mensaje' => "Tu denuncia sobre la publicación 'Naranjas de Laguna Naineck' fue marcada como revisada.",
        'leida' => false,
    ]);

    // Alerta al productor denunciado
    $this->assertDatabaseHas('alertas', [
        'usuario_id' => $productor->id,
        'tipo' => 'denuncia_resuelta',
        'mensaje' => "La denuncia sobre tu publicación 'Naranjas de Laguna Naineck' fue marcada como revisada.",
        'leida' => false,
    ]);
});

test('al resolver una denuncia como descartada se generan alertas para ambas partes', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $denunciante = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Miel Pura',
    ]);

    $denuncia = Denuncia::factory()->create([
        'usuario_id' => $denunciante->id,
        'publicacion_id' => $publicacion->id,
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('admin.denuncias.update', $denuncia), [
            'estado' => Denuncia::ESTADO_DESCARTADA,
        ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('alertas', [
        'usuario_id' => $denunciante->id,
        'tipo' => 'denuncia_resuelta',
        'mensaje' => "Tu denuncia sobre la publicación 'Miel Pura' fue marcada como descartada.",
        'leida' => false,
    ]);

    $this->assertDatabaseHas('alertas', [
        'usuario_id' => $productor->id,
        'tipo' => 'denuncia_resuelta',
        'mensaje' => "La denuncia sobre tu publicación 'Miel Pura' fue marcada como descartada.",
        'leida' => false,
    ]);
});
