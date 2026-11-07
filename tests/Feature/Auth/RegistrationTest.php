<?php

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
        'rol' => 'productor',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration saves the chosen role (productor)', function () {
    $this->post('/register', [
        'name' => 'Productor User',
        'email' => 'productor@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rol' => 'productor',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'productor@example.com',
        'rol' => 'productor',
    ]);
});

test('registration saves the chosen role (comprador)', function () {
    $this->post('/register', [
        'name' => 'Comprador User',
        'email' => 'comprador@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rol' => 'comprador',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'comprador@example.com',
        'rol' => 'comprador',
    ]);
});

test('cannot register as admin from public registration', function () {
    $response = $this->post('/register', [
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'rol' => 'admin',
    ]);

    $response->assertSessionHasErrors('rol');
    $this->assertDatabaseMissing('users', [
        'email' => 'admin@example.com',
    ]);
});
