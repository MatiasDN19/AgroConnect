<?php

use App\Models\Denuncia;
use App\Models\Publicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

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

// --- Listado de Denuncias para Administración (KAN-67 / KAN-69) ---

test('los invitados no autenticados son redirigidos al login al intentar ver el listado de denuncias', function () {
    $response = $this->get(route('admin.denuncias.index'));

    $response->assertRedirect(route('login'));
});

test('los usuarios con rol productor no pueden acceder al listado de denuncias de administración', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $response = $this->actingAs($productor)->get(route('admin.denuncias.index'));

    $response->assertForbidden();
});

test('los usuarios con rol comprador no pueden acceder al listado de denuncias de administración', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $response = $this->actingAs($comprador)->get(route('admin.denuncias.index'));

    $response->assertForbidden();
});

test('el administrador puede ver el listado de denuncias pendientes por defecto', function () {
    $admin = User::factory()->admin()->create();

    $autor = User::factory()->create(['name' => 'Autor Uno', 'rol' => User::ROL_PRODUCTOR]);
    $denunciante = User::factory()->create(['name' => 'Denunciante Uno', 'rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $autor->id, 'titulo' => 'Publicación denunciada']);

    $denunciaPendiente = Denuncia::factory()->create([
        'usuario_id' => $denunciante->id,
        'publicacion_id' => $publicacion->id,
        'motivo' => 'Motivo pendiente',
        'descripcion' => 'Descripción pendiente',
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);

    Denuncia::factory()->create([
        'estado' => Denuncia::ESTADO_REVISADA,
    ]);

    Denuncia::factory()->create([
        'estado' => Denuncia::ESTADO_DESCARTADA,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.denuncias.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Denuncias/Index')
        ->where('estado_actual', Denuncia::ESTADO_PENDIENTE)
        ->has('denuncias', 1)
        ->has('denuncias.0', fn (Assert $denuncia) => $denuncia
            ->where('id', $denunciaPendiente->id)
            ->where('motivo', 'Motivo pendiente')
            ->where('descripcion', 'Descripción pendiente')
            ->where('estado', Denuncia::ESTADO_PENDIENTE)
            ->where('usuario.id', $denunciante->id)
            ->where('usuario.name', 'Denunciante Uno')
            ->where('publicacion.id', $publicacion->id)
            ->where('publicacion.titulo', 'Publicación denunciada')
            ->where('publicacion.usuario.id', $autor->id)
            ->where('publicacion.usuario.name', 'Autor Uno')
            ->etc()
        )
    );
});

test('el administrador puede filtrar denuncias por estado revisada y descartada', function () {
    $admin = User::factory()->admin()->create();

    Denuncia::factory()->create(['estado' => Denuncia::ESTADO_PENDIENTE]);
    $denunciaRevisada = Denuncia::factory()->create(['estado' => Denuncia::ESTADO_REVISADA]);
    $denunciaDescartada = Denuncia::factory()->create(['estado' => Denuncia::ESTADO_DESCARTADA]);

    // Filtrar por revisadas
    $responseRevisada = $this->actingAs($admin)
        ->get(route('admin.denuncias.index', ['estado' => Denuncia::ESTADO_REVISADA]));

    $responseRevisada->assertOk();
    $responseRevisada->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Denuncias/Index')
        ->where('estado_actual', Denuncia::ESTADO_REVISADA)
        ->has('denuncias', 1)
        ->where('denuncias.0.id', $denunciaRevisada->id)
    );

    // Filtrar por descartadas
    $responseDescartada = $this->actingAs($admin)
        ->get(route('admin.denuncias.index', ['estado' => Denuncia::ESTADO_DESCARTADA]));

    $responseDescartada->assertOk();
    $responseDescartada->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Denuncias/Index')
        ->where('estado_actual', Denuncia::ESTADO_DESCARTADA)
        ->has('denuncias', 1)
        ->where('denuncias.0.id', $denunciaDescartada->id)
    );
});

// --- Resolución de Denuncias (KAN-70 / KAN-72) ---

test('los invitados no autenticados son redirigidos al login al intentar resolver una denuncia', function () {
    $denuncia = Denuncia::factory()->create(['estado' => Denuncia::ESTADO_PENDIENTE]);

    $response = $this->patch(route('admin.denuncias.update', $denuncia), [
        'estado' => Denuncia::ESTADO_REVISADA,
    ]);

    $response->assertRedirect(route('login'));
    expect($denuncia->fresh()->estado)->toBe(Denuncia::ESTADO_PENDIENTE);
});

test('un usuario con rol productor no puede resolver una denuncia', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $denuncia = Denuncia::factory()->create(['estado' => Denuncia::ESTADO_PENDIENTE]);

    $response = $this->actingAs($productor)
        ->patch(route('admin.denuncias.update', $denuncia), [
            'estado' => Denuncia::ESTADO_REVISADA,
        ]);

    $response->assertForbidden();
    expect($denuncia->fresh()->estado)->toBe(Denuncia::ESTADO_PENDIENTE);
});

test('un usuario con rol comprador no puede resolver una denuncia', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $denuncia = Denuncia::factory()->create(['estado' => Denuncia::ESTADO_PENDIENTE]);

    $response = $this->actingAs($comprador)
        ->patch(route('admin.denuncias.update', $denuncia), [
            'estado' => Denuncia::ESTADO_DESCARTADA,
        ]);

    $response->assertForbidden();
    expect($denuncia->fresh()->estado)->toBe(Denuncia::ESTADO_PENDIENTE);
});

test('el administrador puede marcar una denuncia como revisada', function () {
    $admin = User::factory()->admin()->create();
    $denuncia = Denuncia::factory()->create(['estado' => Denuncia::ESTADO_PENDIENTE]);

    $response = $this->actingAs($admin)
        ->patch(route('admin.denuncias.update', $denuncia), [
            'estado' => Denuncia::ESTADO_REVISADA,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.denuncias.index'));
    $response->assertSessionHas('success', 'Estado de la denuncia actualizado exitosamente.');
    expect($denuncia->fresh()->estado)->toBe(Denuncia::ESTADO_REVISADA);
});

test('el administrador puede marcar una denuncia como descartada', function () {
    $admin = User::factory()->admin()->create();
    $denuncia = Denuncia::factory()->create(['estado' => Denuncia::ESTADO_PENDIENTE]);

    $response = $this->actingAs($admin)
        ->patch(route('admin.denuncias.update', $denuncia), [
            'estado' => Denuncia::ESTADO_DESCARTADA,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.denuncias.index'));
    expect($denuncia->fresh()->estado)->toBe(Denuncia::ESTADO_DESCARTADA);
});

test('la resolución de denuncia valida el campo estado requerido y valores válidos', function () {
    $admin = User::factory()->admin()->create();
    $denuncia = Denuncia::factory()->create(['estado' => Denuncia::ESTADO_PENDIENTE]);

    // Estado requerido
    $responseRequired = $this->actingAs($admin)
        ->patch(route('admin.denuncias.update', $denuncia), []);

    $responseRequired->assertSessionHasErrors([
        'estado' => 'El estado es obligatorio.',
    ]);

    // Estado inválido
    $responseInvalido = $this->actingAs($admin)
        ->patch(route('admin.denuncias.update', $denuncia), [
            'estado' => 'estado_inexistente',
        ]);

    $responseInvalido->assertSessionHasErrors([
        'estado' => 'El estado seleccionado no es válido.',
    ]);

    expect($denuncia->fresh()->estado)->toBe(Denuncia::ESTADO_PENDIENTE);
});
