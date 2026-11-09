<?php

use App\Models\Publicacion;
use App\Models\Resena;
use App\Models\User;

test('los invitados no autenticados son redirigidos al login al intentar crear una reseña', function () {
    $publicacion = Publicacion::factory()->create();

    $response = $this->post(route('publicaciones.resenas.store', $publicacion), [
        'puntuacion' => 5,
        'comentario' => 'Excelente producto.',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertDatabaseEmpty('resenas');
});

test('un usuario autenticado puede dejar una reseña válida en una publicación ajena', function () {
    $autorPublicacion = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $autorPublicacion->id]);

    $payload = [
        'puntuacion' => 4,
        'comentario' => 'Muy buena calidad y entrega a tiempo.',
    ];

    $response = $this->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));
    $response->assertSessionHas('success', 'Reseña creada exitosamente.');

    $this->assertDatabaseHas('resenas', [
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'puntuacion' => 4,
        'comentario' => 'Muy buena calidad y entrega a tiempo.',
    ]);
});

test('el comentario es opcional al crear una reseña', function () {
    $autorPublicacion = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $autorPublicacion->id]);

    $payload = [
        'puntuacion' => 5,
    ];

    $response = $this->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));

    $this->assertDatabaseHas('resenas', [
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'puntuacion' => 5,
        'comentario' => null,
    ]);
});

test('un usuario no puede reseñar su propia publicación', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    $payload = [
        'puntuacion' => 5,
        'comentario' => 'Autoelogio de mis propios tomates.',
    ];

    $response = $this->actingAs($productor)
        ->post(route('publicaciones.resenas.store', $publicacion), $payload);

    $response->assertSessionHasErrors([
        'publicacion' => 'No podés reseñar tu propia publicación.',
    ]);
    $this->assertDatabaseEmpty('resenas');
});

test('un usuario no puede dejar dos reseñas en la misma publicación', function () {
    $autorPublicacion = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $autorPublicacion->id]);

    Resena::factory()->create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'puntuacion' => 4,
        'comentario' => 'Primera reseña.',
    ]);

    $payload = [
        'puntuacion' => 5,
        'comentario' => 'Segunda reseña intento.',
    ];

    $response = $this->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), $payload);

    $response->assertSessionHasErrors([
        'publicacion' => 'Ya dejaste una reseña en esta publicación.',
    ]);

    expect(Resena::where('publicacion_id', $publicacion->id)->count())->toBe(1);
});

test('la creación de reseña valida la puntuación obligatoria', function () {
    $autorPublicacion = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $autorPublicacion->id]);

    $response = $this->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), [
            'comentario' => 'Sin puntuacion.',
        ]);

    $response->assertSessionHasErrors([
        'puntuacion' => 'La puntuación es obligatoria.',
    ]);
    $this->assertDatabaseEmpty('resenas');
});

test('la creación de reseña rechaza puntuaciones menores a 1 o mayores a 5', function () {
    $autorPublicacion = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $autorPublicacion->id]);

    $responseMenor = $this->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), [
            'puntuacion' => 0,
        ]);

    $responseMenor->assertSessionHasErrors([
        'puntuacion' => 'La puntuación mínima es 1.',
    ]);

    $responseMayor = $this->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), [
            'puntuacion' => 6,
        ]);

    $responseMayor->assertSessionHasErrors([
        'puntuacion' => 'La puntuación máxima es 5.',
    ]);

    $this->assertDatabaseEmpty('resenas');
});

test('la creación de reseña rechaza comentarios que exceden los 2000 caracteres', function () {
    $autorPublicacion = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $autorPublicacion->id]);

    $response = $this->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), [
            'puntuacion' => 4,
            'comentario' => str_repeat('a', 2001),
        ]);

    $response->assertSessionHasErrors(['comentario']);
    $this->assertDatabaseEmpty('resenas');
});
