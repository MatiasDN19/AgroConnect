<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('publicaciones.index', absolute: false));
});

test('registration saves the chosen role (productor)', function () {
    $response = $this->post('/register', [
        'name' => 'Productor User',
        'email' => 'productor@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.index', absolute: false));

    $this->assertDatabaseHas('users', [
        'email' => 'productor@example.com',
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $user = User::where('email', 'productor@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->esProductor())->toBeTrue();
    expect($user->esAdmin())->toBeFalse();
});

test('registration saves the chosen role (comprador)', function () {
    $response = $this->post('/register', [
        'name' => 'Comprador User',
        'email' => 'comprador@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rol' => User::ROL_COMPRADOR,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.index', absolute: false));

    $this->assertDatabaseHas('users', [
        'email' => 'comprador@example.com',
        'rol' => User::ROL_COMPRADOR,
    ]);

    $user = User::where('email', 'comprador@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->esComprador())->toBeTrue();
    expect($user->esAdmin())->toBeFalse();
});

test('cannot register as admin from public registration', function () {
    $response = $this->post('/register', [
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rol' => User::ROL_ADMIN,
    ]);

    $response->assertSessionHasErrors('rol');
    $this->assertDatabaseMissing('users', [
        'email' => 'admin@example.com',
    ]);
});

test('registration requires a role', function () {
    $response = $this->post('/register', [
        'name' => 'No Role User',
        'email' => 'norole@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('rol');
    $this->assertDatabaseMissing('users', [
        'email' => 'norole@example.com',
    ]);
});

test('registration rejects invalid roles', function () {
    $response = $this->post('/register', [
        'name' => 'Invalid Role User',
        'email' => 'invalidrole@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rol' => 'moderador',
    ]);

    $response->assertSessionHasErrors('rol');
    $this->assertDatabaseMissing('users', [
        'email' => 'invalidrole@example.com',
    ]);
});

test('validation error messages are returned in spanish', function () {
    $response = $this->post('/register', [
        'name' => '',
        'email' => 'correo-invalido',
        'password' => 'corta',
        'password_confirmation' => 'otra',
    ]);

    $response->assertSessionHasErrors([
        'name' => 'El campo nombre es obligatorio.',
        'email' => 'El campo correo electrónico debe ser una dirección de correo electrónico válida.',
        'password' => 'La confirmación del campo contraseña no coincide.',
    ]);
});
