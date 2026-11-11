<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;

test('una publicación activa pasa a pausada automáticamente cuando su stock se actualiza a 0', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'stock' => 15,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $publicacion->update(['stock' => 0]);

    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_PAUSADA);
});

test('una publicación pausada por falta de stock vuelve a activa automáticamente cuando se le repone stock', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'stock' => 0,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    $publicacion->update(['stock' => 20]);

    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_ACTIVA);
});

test('una publicación marcada vendida a mano no cambia de estado aunque su stock llegue a 0 o se le reponga stock', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'stock' => 10,
        'estado' => Publicacion::ESTADO_VENDIDA,
    ]);

    // Actualizar a stock 0
    $publicacion->update(['stock' => 0]);
    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_VENDIDA);

    // Reponer stock
    $publicacion->update(['stock' => 50]);
    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_VENDIDA);
});

test('el cambio explícito de estado a mano se respeta aunque el stock no sea 0', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'stock' => 15,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    // Productor pausa manualmente teniendo stock > 0
    $publicacion->update(['estado' => Publicacion::ESTADO_PAUSADA]);
    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_PAUSADA);

    // Si cambia el título o descripción sin cambiar stock, no debe reactivarse
    $publicacion->update(['titulo' => 'Nuevo título con pausa manual']);
    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_PAUSADA);
});
