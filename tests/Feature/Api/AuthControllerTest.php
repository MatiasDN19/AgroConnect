<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('iniciar sesion con credenciales correctas devuelve 200 y un token bearer', function () {
    $usuario = User::factory()->create([
        'email' => 'productor@agroconnect.test',
        'password' => Hash::make('password123'),
        'rol' => User::ROL_PRODUCTOR,
        'telefono' => '3718123456',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'productor@agroconnect.test',
        'password' => 'password123',
    ]);

    $response->assertOk();
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
    expect($response->json('user.id'))->toBe($usuario->id);
    expect($response->json('user.email'))->toBe('productor@agroconnect.test');
    expect($response->json('user.rol'))->toBe(User::ROL_PRODUCTOR);
});

test('iniciar sesion con contrasena incorrecta devuelve 422 y ningun token', function () {
    User::factory()->create([
        'email' => 'productor@agroconnect.test',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'productor@agroconnect.test',
        'password' => 'clave-erronea',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
    $response->assertJsonMissing(['token']);
});

test('iniciar sesion con email inexistente devuelve 422', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'inexistente@agroconnect.test',
        'password' => 'password123',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});

test('iniciar sesion requiere email y contrasena', function () {
    $response = $this->postJson('/api/login', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email', 'password']);
});

test('cerrar sesion sin autenticacion devuelve 401 unauthenticated', function () {
    $response = $this->postJson('/api/logout');

    $response->assertUnauthorized();
});

test('cerrar sesion con token valido revoca el token impidiendo accesos posteriores', function () {
    $usuario = User::factory()->create([
        'email' => 'usuario@agroconnect.test',
        'password' => Hash::make('password123'),
    ]);

    $loginResponse = $this->postJson('/api/login', [
        'email' => 'usuario@agroconnect.test',
        'password' => 'password123',
    ]);

    $loginResponse->assertOk();
    $token = $loginResponse->json('token');

    // Con el token podemos consultar /api/user
    $userResponse = $this->withToken($token)->getJson('/api/user');
    $userResponse->assertOk();
    expect($userResponse->json('id'))->toBe($usuario->id);

    // Ejecutar logout con el token
    $logoutResponse = $this->withToken($token)->postJson('/api/logout');
    $logoutResponse->assertOk();
    $logoutResponse->assertJson([
        'message' => 'Sesión cerrada correctamente.',
    ]);

    $this->assertDatabaseEmpty('personal_access_tokens');

    // Resetear el estado de auth en memoria para simular una nueva petición HTTP independiente
    app('auth')->forgetGuards();

    // La siguiente petición con el token revocado debe fallar con 401
    $afterLogoutResponse = $this->withToken($token)->getJson('/api/user');
    $afterLogoutResponse->assertUnauthorized();
});
