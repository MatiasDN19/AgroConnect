<?php

use App\Models\Publicacion;
use App\Models\Resena;
use App\Models\User;

it('permite al productor crear publicaciones', function () {
    $productor = User::factory()->create();

    expect($productor->can('create', Publicacion::class))->toBeTrue();
});

it('no permite al admin crear publicaciones', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('create', Publicacion::class))->toBeFalse();
});

it('permite al dueño editar y borrar su publicacion', function () {
    $productor = User::factory()->create();
    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    expect($productor->can('update', $publicacion))->toBeTrue()
        ->and($productor->can('delete', $publicacion))->toBeTrue();
});

it('no permite a otro productor editar una publicacion ajena', function () {
    $otro = User::factory()->create();
    $publicacion = Publicacion::factory()->create();

    expect($otro->can('update', $publicacion))->toBeFalse()
        ->and($otro->can('delete', $publicacion))->toBeFalse();
});

it('permite al admin editar y borrar cualquier publicacion', function () {
    $admin = User::factory()->admin()->create();
    $publicacion = Publicacion::factory()->create();

    expect($admin->can('update', $publicacion))->toBeTrue()
        ->and($admin->can('delete', $publicacion))->toBeTrue();
});

it('calcula el promedio de puntuacion de una publicacion', function () {
    $publicacion = Publicacion::factory()->create();
    Resena::factory()->create(['publicacion_id' => $publicacion->id, 'puntuacion' => 5]);
    Resena::factory()->create(['publicacion_id' => $publicacion->id, 'puntuacion' => 3]);

    expect($publicacion->puntuacionPromedio())->toBe(4.0);
});
