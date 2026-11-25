<?php

use App\Models\User;

test('un admin puede promover a un productor al rol admin', function () {
    $admin = User::factory()->admin()->create();
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.usuarios.index'))
        ->patch(route('admin.usuarios.rol', $productor), [
            'rol' => User::ROL_ADMIN,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.usuarios.index'))
        ->assertSessionHas('success', 'Rol de usuario actualizado correctamente.');

    $productor->refresh();

    expect($productor->rol)->toBe(User::ROL_ADMIN);
    expect($productor->esAdmin())->toBeTrue();

    $this->assertDatabaseHas('users', [
        'id' => $productor->id,
        'rol' => User::ROL_ADMIN,
    ]);
});

test('un admin puede quitar el rol admin a otro usuario asignandole rol productor o comprador', function () {
    $adminPrincipal = User::factory()->admin()->create();
    $otroAdmin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($adminPrincipal)
        ->from(route('admin.usuarios.index'))
        ->patch(route('admin.usuarios.rol', $otroAdmin), [
            'rol' => User::ROL_PRODUCTOR,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.usuarios.index'))
        ->assertSessionHas('success', 'Rol de usuario actualizado correctamente.');

    $otroAdmin->refresh();

    expect($otroAdmin->rol)->toBe(User::ROL_PRODUCTOR);
    expect($otroAdmin->esAdmin())->toBeFalse();

    $this->assertDatabaseHas('users', [
        'id' => $otroAdmin->id,
        'rol' => User::ROL_PRODUCTOR,
    ]);
});

test('un admin no puede cambiarse el rol a si mismo', function () {
    $adminPrincipal = User::factory()->admin()->create();
    User::factory()->admin()->create(); // Hay otro admin para verificar que la regla propia es la que bloquea

    $response = $this
        ->actingAs($adminPrincipal)
        ->patch(route('admin.usuarios.rol', $adminPrincipal), [
            'rol' => User::ROL_PRODUCTOR,
        ]);

    $response->assertForbidden();

    $adminPrincipal->refresh();

    expect($adminPrincipal->rol)->toBe(User::ROL_ADMIN);
    expect($adminPrincipal->esAdmin())->toBeTrue();
});

test('no se puede quitar el rol de administrador al unico admin de la plataforma', function () {
    $unicoAdmin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($unicoAdmin)
        ->from(route('admin.usuarios.index'))
        ->patch(route('admin.usuarios.rol', $unicoAdmin), [
            'rol' => User::ROL_PRODUCTOR,
        ]);

    $response
        ->assertRedirect(route('admin.usuarios.index'))
        ->assertSessionHas('error', 'No se puede quitar el rol al único administrador de la plataforma.');

    $unicoAdmin->refresh();

    expect($unicoAdmin->rol)->toBe(User::ROL_ADMIN);
    expect($unicoAdmin->esAdmin())->toBeTrue();
});

test('un usuario no-admin recibe 403 al intentar cambiar roles', function () {
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
    ]);
    $otroUsuario = User::factory()->create([
        'rol' => User::ROL_COMPRADOR,
    ]);

    $response = $this
        ->actingAs($productor)
        ->patch(route('admin.usuarios.rol', $otroUsuario), [
            'rol' => User::ROL_ADMIN,
        ]);

    $response->assertForbidden();

    $otroUsuario->refresh();
    expect($otroUsuario->rol)->toBe(User::ROL_COMPRADOR);
});

test('los invitados no autenticados son redirigidos al login al intentar cambiar roles', function () {
    $usuario = User::factory()->create();

    $response = $this->patch(route('admin.usuarios.rol', $usuario), [
        'rol' => User::ROL_ADMIN,
    ]);

    $response->assertRedirect(route('login'));

    $usuario->refresh();
    expect($usuario->rol)->not->toBe(User::ROL_ADMIN);
});

test('el rol debe ser un valor valido del sistema', function () {
    $admin = User::factory()->admin()->create();
    $usuario = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('admin.usuarios.rol', $usuario), [
            'rol' => 'rol_inexistente',
        ]);

    $response->assertSessionHasErrors(['rol']);

    $usuario->refresh();
    expect($usuario->rol)->toBe(User::ROL_PRODUCTOR);
});
