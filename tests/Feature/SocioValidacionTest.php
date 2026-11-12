<?php

use App\Models\User;

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
