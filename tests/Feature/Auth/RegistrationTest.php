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
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('publicaciones.index', absolute: false));
});

test('registration saves the chosen role (productor)', function () {
    $response = $this->post('/register', [
        'name' => 'Productor User',
        'email' => 'productor@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
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
        'password' => 'password123',
        'password_confirmation' => 'password123',
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
        'password' => 'password123',
        'password_confirmation' => 'password123',
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
        'password' => 'password123',
        'password_confirmation' => 'password123',
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
        'password' => 'password123',
        'password_confirmation' => 'password123',
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

test('registration fails when password has no letters', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'noletters@example.com',
        'password' => '12345678',
        'password_confirmation' => '12345678',
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $response->assertSessionHasErrors([
        'password' => 'La contraseña debe contener al menos una letra.',
    ]);
});

test('registration fails when password has no numbers', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'nonumbers@example.com',
        'password' => 'abcdefgh',
        'password_confirmation' => 'abcdefgh',
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $response->assertSessionHasErrors([
        'password' => 'La contraseña debe contener al menos un número.',
    ]);
});

test('registration fails when password has less than 8 characters', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'short@example.com',
        'password' => 'abc12',
        'password_confirmation' => 'abc12',
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $response->assertSessionHasErrors('password');
});

test('registration succeeds with strong password (agro2026campo)', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'agro@example.com',
        'password' => 'agro2026campo',
        'password_confirmation' => 'agro2026campo',
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});
