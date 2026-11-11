<?php

use App\Models\Denuncia;
use App\Models\Publicacion;
use App\Models\User;

test('los invitados no autenticados son redirigidos al login al intentar denunciar una publicación', function () {
    $publicacion = Publicacion::factory()->create();

    $response = $this->post(route('publicaciones.denuncias.store', $publicacion), [
        'motivo' => 'Publicación engañosa',
        'descripcion' => 'El producto no coincide con la foto.',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertDatabaseEmpty('denuncias');
});

test('un usuario autenticado puede denunciar una publicación ajena', function () {
    $autor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $denunciante = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $autor->id]);

    $payload = [
        'motivo' => 'Precio engañoso',
        'descripcion' => 'El precio publicado no se corresponde con el cobrado.',
    ];

    $response = $this->actingAs($denunciante)
        ->post(route('publicaciones.denuncias.store', $publicacion), $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));
    $response->assertSessionHas('success', 'Denuncia enviada exitosamente.');

    $this->assertDatabaseHas('denuncias', [
        'usuario_id' => $denunciante->id,
        'publicacion_id' => $publicacion->id,
        'motivo' => 'Precio engañoso',
        'descripcion' => 'El precio publicado no se corresponde con el cobrado.',
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);
});

test('la descripción es opcional al crear una denuncia', function () {
    $autor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $denunciante = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $autor->id]);

    $payload = [
        'motivo' => 'Producto en mal estado',
    ];

    $response = $this->actingAs($denunciante)
        ->post(route('publicaciones.denuncias.store', $publicacion), $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));

    $this->assertDatabaseHas('denuncias', [
        'usuario_id' => $denunciante->id,
        'publicacion_id' => $publicacion->id,
        'motivo' => 'Producto en mal estado',
        'descripcion' => null,
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);
});

test('un usuario no puede denunciar su propia publicación', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    $payload = [
        'motivo' => 'Auto-denuncia errónea',
        'descripcion' => 'Intento denunciar mi propia publicación.',
    ];

    $response = $this->actingAs($productor)
        ->post(route('publicaciones.denuncias.store', $publicacion), $payload);

    $response->assertSessionHasErrors([
        'publicacion' => 'No podés denunciar tu propia publicación.',
    ]);
    $this->assertDatabaseEmpty('denuncias');
});

test('un usuario no puede denunciar dos veces la misma publicación', function () {
    $autor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $denunciante = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $autor->id]);

    Denuncia::factory()->create([
        'usuario_id' => $denunciante->id,
        'publicacion_id' => $publicacion->id,
        'motivo' => 'Primer motivo',
    ]);

    $payload = [
        'motivo' => 'Segundo intento de denuncia',
        'descripcion' => 'Insisto con la denuncia.',
    ];

    $response = $this->actingAs($denunciante)
        ->post(route('publicaciones.denuncias.store', $publicacion), $payload);

    $response->assertSessionHasErrors([
        'publicacion' => 'Ya denunciaste esta publicación.',
    ]);

    expect(Denuncia::where('publicacion_id', $publicacion->id)->count())->toBe(1);
});

test('dos usuarios distintos pueden denunciar la misma publicación cada uno por separado', function () {
    $autor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $denunciante1 = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $denunciante2 = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $autor->id]);

    // Denuncia del primer usuario
    $response1 = $this->actingAs($denunciante1)
        ->post(route('publicaciones.denuncias.store', $publicacion), [
            'motivo' => 'Denuncia usuario 1',
        ]);
    $response1->assertSessionHasNoErrors();

    // Denuncia del segundo usuario
    $response2 = $this->actingAs($denunciante2)
        ->post(route('publicaciones.denuncias.store', $publicacion), [
            'motivo' => 'Denuncia usuario 2',
        ]);
    $response2->assertSessionHasNoErrors();

    expect(Denuncia::where('publicacion_id', $publicacion->id)->count())->toBe(2);
});

test('la creación de denuncia valida el campo motivo obligatorio y límite de caracteres', function () {
    $autor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $denunciante = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $autor->id]);

    // Sin motivo
    $responseSinMotivo = $this->actingAs($denunciante)
        ->post(route('publicaciones.denuncias.store', $publicacion), [
            'descripcion' => 'Solo descripción sin motivo.',
        ]);
    $responseSinMotivo->assertSessionHasErrors([
        'motivo' => 'El motivo es obligatorio.',
    ]);

    // Motivo excedido
    $responseMotivoLargo = $this->actingAs($denunciante)
        ->post(route('publicaciones.denuncias.store', $publicacion), [
            'motivo' => str_repeat('a', 256),
        ]);
    $responseMotivoLargo->assertSessionHasErrors([
        'motivo' => 'El motivo no puede superar los 255 caracteres.',
    ]);

    // Descripción excedida
    $responseDescLarga = $this->actingAs($denunciante)
        ->post(route('publicaciones.denuncias.store', $publicacion), [
            'motivo' => 'Motivo válido',
            'descripcion' => str_repeat('a', 2001),
        ]);
    $responseDescLarga->assertSessionHasErrors([
        'descripcion' => 'La descripción no puede superar los 2000 caracteres.',
    ]);

    $this->assertDatabaseEmpty('denuncias');
});
