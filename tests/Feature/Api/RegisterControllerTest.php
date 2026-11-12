<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

test('registrar un usuario con datos validos crea el usuario con su rol y telefono y devuelve un token', function () {
    Event::fake();

    $payload = [
        'name' => 'Don Vicente',
        'email' => 'vicente@agroconnect.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rol' => User::ROL_PRODUCTOR,
        'telefono' => '3718998877',
    ];

    $response = $this->postJson('/api/register', $payload);

    $response->assertCreated();
    $response->assertJsonStructure([
        'token',
        'user' => [
            'id',
            'name',
            'email',
            'rol',
            'telefono',
            'lat',
            'lng',
        ],
    ]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
    expect($response->json('user.name'))->toBe('Don Vicente');
    expect($response->json('user.email'))->toBe('vicente@agroconnect.test');
    expect($response->json('user.rol'))->toBe(User::ROL_PRODUCTOR);
    expect($response->json('user.telefono'))->toBe('3718998877');

    $this->assertDatabaseHas('users', [
        'name' => 'Don Vicente',
        'email' => 'vicente@agroconnect.test',
        'rol' => User::ROL_PRODUCTOR,
        'telefono' => '3718998877',
    ]);

    Event::assertDispatched(Registered::class);
});

test('registrar un comprador guarda el rol correspondiente', function () {
    $payload = [
        'name' => 'Comprador Local',
        'email' => 'comprador@agroconnect.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rol' => User::ROL_COMPRADOR,
    ];

    $response = $this->postJson('/api/register', $payload);

    $response->assertCreated();
    expect($response->json('user.rol'))->toBe(User::ROL_COMPRADOR);

    $this->assertDatabaseHas('users', [
        'email' => 'comprador@agroconnect.test',
        'rol' => User::ROL_COMPRADOR,
        'telefono' => null,
    ]);
});

test('registrar con email duplicado devuelve error 422', function () {
    User::factory()->create([
        'email' => 'existente@agroconnect.test',
    ]);

    $payload = [
        'name' => 'Otro Usuario',
        'email' => 'existente@agroconnect.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rol' => User::ROL_PRODUCTOR,
    ];

    $response = $this->postJson('/api/register', $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertJsonMissing(['token']);
});

test('registrar requiere name, email, password y rol', function () {
    $response = $this->postJson('/api/register', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'email', 'password', 'rol']);
});

test('registrar requiere confirmacion de password coincidente', function () {
    $payload = [
        'name' => 'Juan Perez',
        'email' => 'juan@agroconnect.test',
        'password' => 'password123',
        'password_confirmation' => 'otra-password',
        'rol' => User::ROL_PRODUCTOR,
    ];

    $response = $this->postJson('/api/register', $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('no se puede asignar el rol admin desde el endpoint de registro', function () {
    $payload = [
        'name' => 'Intento Admin',
        'email' => 'hacker@agroconnect.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rol' => User::ROL_ADMIN,
    ];

    $response = $this->postJson('/api/register', $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['rol']);
    $this->assertDatabaseMissing('users', [
        'email' => 'hacker@agroconnect.test',
    ]);
});
