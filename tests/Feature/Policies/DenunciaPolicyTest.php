<?php

use App\Models\Denuncia;
use App\Models\User;

it('solo el admin puede ver el listado de denuncias', function () {
    $admin = User::factory()->admin()->create();
    $productor = User::factory()->create();

    expect($admin->can('viewAny', Denuncia::class))->toBeTrue()
        ->and($productor->can('viewAny', Denuncia::class))->toBeFalse();
});

it('cualquier usuario autenticado puede crear una denuncia', function () {
    $user = User::factory()->create();

    expect($user->can('create', Denuncia::class))->toBeTrue();
});

it('solo el admin puede actualizar el estado de una denuncia', function () {
    $admin = User::factory()->admin()->create();
    $denunciante = User::factory()->create();
    $denuncia = Denuncia::factory()->create(['usuario_id' => $denunciante->id]);

    expect($admin->can('update', $denuncia))->toBeTrue()
        ->and($denunciante->can('update', $denuncia))->toBeFalse();
});
