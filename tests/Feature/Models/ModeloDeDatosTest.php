<?php

use App\Models\Alerta;
use App\Models\Categoria;
use App\Models\Denuncia;
use App\Models\Publicacion;
use App\Models\Resena;
use App\Models\User;
use Illuminate\Database\QueryException;

it('arma correctamente las relaciones del modelo', function () {
    $productor = User::factory()->create();
    $categoria = Categoria::factory()->create();
    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
    ]);

    expect($publicacion->usuario->is($productor))->toBeTrue()
        ->and($publicacion->categoria->is($categoria))->toBeTrue()
        ->and($productor->publicaciones)->toHaveCount(1)
        ->and($categoria->publicaciones)->toHaveCount(1);
});

it('no deja que un usuario reseñe dos veces la misma publicacion', function () {
    $user = User::factory()->create();
    $publicacion = Publicacion::factory()->create();

    Resena::factory()->create(['usuario_id' => $user->id, 'publicacion_id' => $publicacion->id]);

    expect(fn () => Resena::factory()->create(['usuario_id' => $user->id, 'publicacion_id' => $publicacion->id]))
        ->toThrow(QueryException::class);
});

it('identifica correctamente el rol del usuario', function () {
    $productor = User::factory()->create();
    $admin = User::factory()->admin()->create();

    expect($productor->esProductor())->toBeTrue()
        ->and($productor->esAdmin())->toBeFalse()
        ->and($admin->esAdmin())->toBeTrue()
        ->and($admin->esProductor())->toBeFalse();
});

it('relaciona denuncias y alertas con su usuario', function () {
    $user = User::factory()->create();
    Denuncia::factory()->create(['usuario_id' => $user->id]);
    Alerta::factory()->create(['usuario_id' => $user->id]);

    expect($user->denuncias)->toHaveCount(1)
        ->and($user->alertas)->toHaveCount(1);
});
