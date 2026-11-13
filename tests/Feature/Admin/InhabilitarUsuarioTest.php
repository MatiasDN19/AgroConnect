<?php

use App\Models\User;

test('un admin puede inhabilitar a otro usuario', function () {
    $admin = User::factory()->admin()->create();
    $otroUsuario = User::factory()->create([
        'inhabilitado' => false,
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('dashboard'))
        ->patch(route('admin.usuarios.inhabilitar', $otroUsuario));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success', 'Usuario inhabilitado correctamente.');

    $otroUsuario->refresh();

    expect($otroUsuario->inhabilitado)->toBeTrue();
    expect($otroUsuario->estaInhabilitado())->toBeTrue();

    $this->assertDatabaseHas('users', [
        'id' => $otroUsuario->id,
        'inhabilitado' => true,
    ]);
});

test('un admin puede reactivar a un usuario inhabilitado', function () {
    $admin = User::factory()->admin()->create();
    $otroUsuario = User::factory()->inhabilitado()->create();

    expect($otroUsuario->estaInhabilitado())->toBeTrue();

    $response = $this
        ->actingAs($admin)
        ->from(route('dashboard'))
        ->patch(route('admin.usuarios.inhabilitar', $otroUsuario));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success', 'Usuario reactivado correctamente.');

    $otroUsuario->refresh();

    expect($otroUsuario->inhabilitado)->toBeFalse();
    expect($otroUsuario->estaInhabilitado())->toBeFalse();

    $this->assertDatabaseHas('users', [
        'id' => $otroUsuario->id,
        'inhabilitado' => false,
    ]);
});

test('un usuario inhabilitado no puede iniciar sesion aunque la contrasena sea correcta', function () {
    $usuario = User::factory()->inhabilitado()->create([
        'password' => 'password',
    ]);

    $response = $this->post('/login', [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $this->assertGuest();

    $response->assertSessionHasErrors([
        'email' => 'Tu cuenta fue inhabilitada, contactá al administrador.',
    ]);
});

test('un usuario inhabilitado no puede iniciar sesion via api movil', function () {
    $usuario = User::factory()->inhabilitado()->create([
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'email' => 'Tu cuenta fue inhabilitada, contactá al administrador.',
        ]);
});

test('un admin no puede inhabilitarse a si mismo', function () {
    $admin = User::factory()->admin()->create([
        'inhabilitado' => false,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('admin.usuarios.inhabilitar', $admin));

    $response->assertForbidden();

    $admin->refresh();

    expect($admin->inhabilitado)->toBeFalse();
    expect($admin->estaInhabilitado())->toBeFalse();

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'inhabilitado' => false,
    ]);
});

test('un usuario no-admin recibe 403 al intentar inhabilitar a otro usuario', function () {
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
    ]);
    $otroUsuario = User::factory()->create([
        'inhabilitado' => false,
    ]);

    $response = $this
        ->actingAs($productor)
        ->patch(route('admin.usuarios.inhabilitar', $otroUsuario));

    $response->assertForbidden();

    $otroUsuario->refresh();
    expect($otroUsuario->estaInhabilitado())->toBeFalse();
});

test('los invitados no autenticados son redirigidos al login al intentar inhabilitar', function () {
    $usuario = User::factory()->create([
        'inhabilitado' => false,
    ]);

    $response = $this->patch(route('admin.usuarios.inhabilitar', $usuario));

    $response->assertRedirect(route('login'));

    $usuario->refresh();
    expect($usuario->estaInhabilitado())->toBeFalse();
});
