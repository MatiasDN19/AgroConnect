<?php

use App\Models\Publicacion;
use App\Models\SolicitudCompra;
use App\Models\User;

test('los invitados no autenticados son redirigidos al login al intentar crear una solicitud de compra', function () {
    $publicacion = Publicacion::factory()->create();

    $response = $this->post(route('solicitudes-compra.store', $publicacion), [
        'mensaje' => 'Hola, quiero comprar este producto.',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertDatabaseEmpty('solicitudes_compra');
});

test('un comprador puede crear una solicitud de compra sobre una publicación ajena y queda en estado pendiente', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Miel pura de monte',
    ]);

    $response = $this->actingAs($comprador)
        ->from(route('publicaciones.show', $publicacion))
        ->post(route('solicitudes-compra.store', $publicacion), [
            'mensaje' => 'Hola, ¿tenés disponible para retirar este fin de semana?',
        ]);

    $response->assertRedirect(route('publicaciones.show', $publicacion));
    $response->assertSessionHas('success', 'Solicitud de compra enviada exitosamente.');

    $this->assertDatabaseHas('solicitudes_compra', [
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'mensaje' => 'Hola, ¿tenés disponible para retirar este fin de semana?',
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);
});

test('un usuario no puede crear una solicitud de compra sobre su propia publicación', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Tomates frescos',
    ]);

    $response = $this->actingAs($productor)
        ->from(route('publicaciones.show', $publicacion))
        ->post(route('solicitudes-compra.store', $publicacion), [
            'mensaje' => 'Intentando auto-comprar',
        ]);

    $response->assertSessionHasErrors([
        'publicacion' => 'No podés solicitar la compra de tu propia publicación.',
    ]);

    $this->assertDatabaseEmpty('solicitudes_compra');
});

test('el mensaje es opcional al crear una solicitud de compra', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
    ]);

    $response = $this->actingAs($comprador)
        ->from(route('publicaciones.show', $publicacion))
        ->post(route('solicitudes-compra.store', $publicacion), []);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));

    $this->assertDatabaseHas('solicitudes_compra', [
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'mensaje' => null,
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);
});
