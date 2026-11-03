<?php

use App\Models\Resena;
use App\Models\User;

it('solo el autor de la reseña puede editarla o borrarla', function () {
    $autor = User::factory()->create();
    $otro = User::factory()->create();
    $resena = Resena::factory()->create(['usuario_id' => $autor->id]);

    expect($autor->can('update', $resena))->toBeTrue()
        ->and($autor->can('delete', $resena))->toBeTrue()
        ->and($otro->can('update', $resena))->toBeFalse()
        ->and($otro->can('delete', $resena))->toBeFalse();
});

it('el admin puede moderar cualquier reseña', function () {
    $admin = User::factory()->admin()->create();
    $resena = Resena::factory()->create();

    expect($admin->can('update', $resena))->toBeTrue()
        ->and($admin->can('delete', $resena))->toBeTrue();
});
