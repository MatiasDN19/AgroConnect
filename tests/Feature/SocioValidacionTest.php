<?php

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
