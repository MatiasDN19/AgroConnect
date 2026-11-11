<?php

use App\Models\Publicacion;
use App\Models\User;

test('los invitados no autenticados son redirigidos al login al consultar el endpoint del mapa', function () {
    $response = $this->getJson(route('publicaciones.mapa'));

    $response->assertUnauthorized();
});

test('el endpoint del mapa devuelve las publicaciones activas cuyo dueño tiene ubicación', function () {
    $usuario = User::factory()->create();
    $productorConUbicacion = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'lat' => -25.2458123,
        'lng' => -57.7345678,
    ]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productorConUbicacion->id,
        'titulo' => 'Mandarinas de Laguna Naineck',
        'precio' => 1500.50,
        'unidad' => 'cajón',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($usuario)->getJson(route('publicaciones.mapa'));

    $response->assertOk();
    $response->assertJson([
        [
            'id' => $publicacion->id,
            'titulo' => 'Mandarinas de Laguna Naineck',
            'precio' => '1500.50',
            'unidad' => 'cajón',
            'lat' => -25.2458123,
            'lng' => -57.7345678,
        ],
    ]);
});

test('publicaciones de productores sin ubicación no aparecen en el mapa', function () {
    $usuario = User::factory()->create();
    $productorSinUbicacion = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'lat' => null,
        'lng' => null,
    ]);

    Publicacion::factory()->create([
        'usuario_id' => $productorSinUbicacion->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($usuario)->getJson(route('publicaciones.mapa'));

    $response->assertOk();
    $response->assertJsonCount(0);
});

test('publicaciones pausadas o vendidas no aparecen en el mapa aunque el dueño tenga ubicación', function () {
    $usuario = User::factory()->create();
    $productorConUbicacion = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'lat' => -25.2458123,
        'lng' => -57.7345678,
    ]);

    // Pausada
    Publicacion::factory()->create([
        'usuario_id' => $productorConUbicacion->id,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    // Vendida
    Publicacion::factory()->create([
        'usuario_id' => $productorConUbicacion->id,
        'estado' => Publicacion::ESTADO_VENDIDA,
    ]);

    $response = $this->actingAs($usuario)->getJson(route('publicaciones.mapa'));

    $response->assertOk();
    $response->assertJsonCount(0);
});
