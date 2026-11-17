<?php

use App\Models\Publicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('los invitados no autenticados no pueden actualizar su numero de socio', function () {
    $response = $this->patch(route('profile.socio.update'), [
        'numero_socio' => 'SOCIO-123',
    ]);

    $response->assertRedirect(route('login'));
});

test('un usuario autenticado puede guardar su numero de socio y queda como pendiente de validacion', function () {
    $user = User::factory()->create([
        'numero_socio' => null,
        'socio_validado' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.socio.update'), [
            'numero_socio' => 'SOCIO-456',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'socio-actualizado');

    $user->refresh();

    expect($user->numero_socio)->toBe('SOCIO-456');
    expect($user->socio_validado)->toBeFalse();
    expect($user->esSocioValidado())->toBeFalse();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'numero_socio' => 'SOCIO-456',
        'socio_validado' => false,
    ]);
});

test('si un socio ya validado cambia su numero de socio vuelve a quedar sin validar', function () {
    $user = User::factory()->create([
        'numero_socio' => 'SOCIO-ORIGINAL',
        'socio_validado' => true,
    ]);

    expect($user->esSocioValidado())->toBeTrue();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.socio.update'), [
            'numero_socio' => 'SOCIO-NUEVO',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->numero_socio)->toBe('SOCIO-NUEVO');
    expect($user->socio_validado)->toBeFalse();
    expect($user->esSocioValidado())->toBeFalse();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'numero_socio' => 'SOCIO-NUEVO',
        'socio_validado' => false,
    ]);
});

test('el numero de socio es obligatorio y no puede superar los 50 caracteres', function () {
    $user = User::factory()->create();

    $responseVacio = $this
        ->actingAs($user)
        ->patch(route('profile.socio.update'), [
            'numero_socio' => '',
        ]);

    $responseVacio->assertSessionHasErrors(['numero_socio']);

    $responseLargo = $this
        ->actingAs($user)
        ->patch(route('profile.socio.update'), [
            'numero_socio' => str_repeat('A', 51),
        ]);

    $responseLargo->assertSessionHasErrors(['numero_socio']);
});

test('un admin puede ver el listado de solicitudes de socio pendientes', function () {
    $admin = User::factory()->admin()->create();
    $pendiente = User::factory()->create([
        'numero_socio' => 'SOCIO-PENDIENTE',
        'socio_validado' => false,
    ]);
    User::factory()->create([
        'numero_socio' => 'SOCIO-VALIDADO',
        'socio_validado' => true,
    ]);
    User::factory()->create([
        'numero_socio' => null,
        'socio_validado' => false,
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.socios.index'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Socios/Index')
            ->has('solicitudes', 1)
            ->where('solicitudes.0.id', $pendiente->id)
            ->where('solicitudes.0.numero_socio', 'SOCIO-PENDIENTE')
        );
});

test('un admin puede aceptar una solicitud de socio', function () {
    $admin = User::factory()->admin()->create();
    $usuario = User::factory()->create([
        'numero_socio' => 'SOCIO-1234',
        'socio_validado' => false,
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.socios.index'))
        ->post(route('admin.socios.aceptar', $usuario));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.socios.index'))
        ->assertSessionHas('success', 'Solicitud de socio aceptada exitosamente.');

    $usuario->refresh();

    expect($usuario->socio_validado)->toBeTrue();
    expect($usuario->numero_socio)->toBe('SOCIO-1234');
    expect($usuario->esSocioValidado())->toBeTrue();

    $this->assertDatabaseHas('users', [
        'id' => $usuario->id,
        'numero_socio' => 'SOCIO-1234',
        'socio_validado' => true,
    ]);
});

test('un admin puede rechazar una solicitud de socio', function () {
    $admin = User::factory()->admin()->create();
    $usuario = User::factory()->create([
        'numero_socio' => 'SOCIO-RECHAZAR',
        'socio_validado' => false,
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.socios.index'))
        ->post(route('admin.socios.rechazar', $usuario));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.socios.index'))
        ->assertSessionHas('success', 'Solicitud de socio rechazada exitosamente.');

    $usuario->refresh();

    expect($usuario->numero_socio)->toBeNull();
    expect($usuario->socio_validado)->toBeFalse();
    expect($usuario->esSocioValidado())->toBeFalse();

    $this->assertDatabaseHas('users', [
        'id' => $usuario->id,
        'numero_socio' => null,
        'socio_validado' => false,
    ]);
});

test('un usuario no-admin no puede listar solicitudes de socio y recibe 403', function () {
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $response = $this
        ->actingAs($productor)
        ->get(route('admin.socios.index'));

    $response->assertForbidden();
});

test('un usuario no-admin no puede aceptar ni rechazar solicitudes y recibe 403', function () {
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
    ]);
    $otroUsuario = User::factory()->create([
        'numero_socio' => 'SOCIO-INTACTO',
        'socio_validado' => false,
    ]);

    $responseAceptar = $this
        ->actingAs($productor)
        ->post(route('admin.socios.aceptar', $otroUsuario));

    $responseAceptar->assertForbidden();

    $responseRechazar = $this
        ->actingAs($productor)
        ->post(route('admin.socios.rechazar', $otroUsuario));

    $responseRechazar->assertForbidden();

    $otroUsuario->refresh();
    expect($otroUsuario->numero_socio)->toBe('SOCIO-INTACTO');
    expect($otroUsuario->socio_validado)->toBeFalse();
});

test('los invitados no autenticados son redirigidos al login al intentar acceder a rutas admin de socios', function () {
    $usuario = User::factory()->create([
        'numero_socio' => 'SOCIO-GUEST',
        'socio_validado' => false,
    ]);

    $responseIndex = $this->get(route('admin.socios.index'));
    $responseIndex->assertRedirect(route('login'));

    $responseAceptar = $this->post(route('admin.socios.aceptar', $usuario));
    $responseAceptar->assertRedirect(route('login'));

    $responseRechazar = $this->post(route('admin.socios.rechazar', $usuario));
    $responseRechazar->assertRedirect(route('login'));

    $usuario->refresh();
    expect($usuario->numero_socio)->toBe('SOCIO-GUEST');
    expect($usuario->socio_validado)->toBeFalse();
});

test('la respuesta de perfil incluye socio_validado en true para socios validados y false para el resto sin exponer numero_socio', function () {
    $socioValidado = User::factory()->create([
        'numero_socio' => 'SOCIO-PRIVADO-1',
        'socio_validado' => true,
    ]);

    $noSocio = User::factory()->create([
        'numero_socio' => null,
        'socio_validado' => false,
    ]);

    $responseSocio = $this
        ->actingAs($socioValidado)
        ->get(route('profile.edit'));

    $responseSocio->assertOk();
    $responseSocio->assertInertia(fn (Assert $page) => $page
        ->component('Profile/Edit')
        ->where('socio_validado', true)
    );

    $responseNoSocio = $this
        ->actingAs($noSocio)
        ->get(route('profile.edit'));

    $responseNoSocio->assertOk();
    $responseNoSocio->assertInertia(fn (Assert $page) => $page
        ->component('Profile/Edit')
        ->where('socio_validado', false)
    );
});

test('el listado y detalle de publicaciones incluyen socio_validado del productor y no exponen numero_socio', function () {
    $lector = User::factory()->create();

    $socio = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'numero_socio' => 'SOCIO-SECRETO-99',
        'socio_validado' => true,
    ]);

    $noSocio = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'numero_socio' => null,
        'socio_validado' => false,
    ]);

    $pubSocio = Publicacion::factory()->create([
        'usuario_id' => $socio->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $pubNoSocio = Publicacion::factory()->create([
        'usuario_id' => $noSocio->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $responseIndex = $this->actingAs($lector)->get(route('publicaciones.index'));
    $responseIndex->assertOk();
    $responseIndex->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Index')
        ->where('publicaciones.0.id', $pubSocio->id)
        ->where('publicaciones.0.usuario.socio_validado', true)
        ->missing('publicaciones.0.usuario.numero_socio')
        ->where('publicaciones.1.id', $pubNoSocio->id)
        ->where('publicaciones.1.usuario.socio_validado', false)
        ->missing('publicaciones.1.usuario.numero_socio')
    );

    $responseShowSocio = $this->actingAs($lector)->get(route('publicaciones.show', $pubSocio));
    $responseShowSocio->assertOk();
    $responseShowSocio->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Show')
        ->where('publicacion.usuario.socio_validado', true)
        ->missing('publicacion.usuario.numero_socio')
    );

    $responseShowNoSocio = $this->actingAs($lector)->get(route('publicaciones.show', $pubNoSocio));
    $responseShowNoSocio->assertOk();
    $responseShowNoSocio->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Show')
        ->where('publicacion.usuario.socio_validado', false)
        ->missing('publicacion.usuario.numero_socio')
    );
});
