<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;

test('el detalle web de publicacion incluye el telefono del productor cuando esta cargado para habilitar contacto por whatsapp', function () {
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'telefono' => '3704123456',
    ]);
    $categoria = Categoria::factory()->create();
    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $response = $this->actingAs($comprador)->get(route('publicaciones.show', $publicacion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Show')
        ->where('publicacion.usuario.telefono', '3704123456')
    );
});

test('el detalle web de publicacion devuelve telefono null cuando el productor no tiene telefono cargado para ocultar boton de whatsapp', function () {
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'telefono' => null,
    ]);
    $categoria = Categoria::factory()->create();
    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $response = $this->actingAs($comprador)->get(route('publicaciones.show', $publicacion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Show')
        ->where('publicacion.usuario.telefono', null)
    );
});

test('el detalle via api de publicacion incluye el telefono del productor cuando esta cargado', function () {
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'telefono' => '3704987654',
    ]);
    $categoria = Categoria::factory()->create();
    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    Sanctum::actingAs($comprador);

    $response = $this->getJson("/api/publicaciones/{$publicacion->id}");

    $response->assertOk();
    $response->assertJsonPath('data.usuario.telefono', '3704987654');
});

test('el detalle via api de publicacion devuelve telefono null cuando el productor no tiene telefono cargado', function () {
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'telefono' => null,
    ]);
    $categoria = Categoria::factory()->create();
    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    Sanctum::actingAs($comprador);

    $response = $this->getJson("/api/publicaciones/{$publicacion->id}");

    $response->assertOk();
    $response->assertJsonPath('data.usuario.telefono', null);
});
