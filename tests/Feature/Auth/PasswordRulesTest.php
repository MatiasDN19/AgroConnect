<?php

use App\Models\User;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

test('la regla por defecto exige al menos 8 caracteres, letras y numeros', function () {
    // Falla por falta de letras
    $validadorSoloNumeros = Validator::make(
        ['password' => '12345678'],
        ['password' => [Password::defaults()]]
    );
    expect($validadorSoloNumeros->fails())->toBeTrue();
    expect($validadorSoloNumeros->errors()->first('password'))->toBe('La contraseña debe contener al menos una letra.');

    // Falla por falta de números
    $validadorSoloLetras = Validator::make(
        ['password' => 'abcdefgh'],
        ['password' => [Password::defaults()]]
    );
    expect($validadorSoloLetras->fails())->toBeTrue();
    expect($validadorSoloLetras->errors()->first('password'))->toBe('La contraseña debe contener al menos un número.');

    // Falla por menos de 8 caracteres
    $validadorCorta = Validator::make(
        ['password' => 'abc12'],
        ['password' => [Password::defaults()]]
    );
    expect($validadorCorta->fails())->toBeTrue();

    // Pasa con 8+ caracteres, letras y números
    $validadorValida = Validator::make(
        ['password' => 'agro2026campo'],
        ['password' => [Password::defaults()]]
    );
    expect($validadorValida->passes())->toBeTrue();
});

test('registro por api rechaza contraseñas debiles y acepta contraseñas fuertes', function () {
    // Falla sin letras
    $responseSinLetras = $this->postJson('/api/register', [
        'name' => 'API User',
        'email' => 'api_noletters@example.com',
        'password' => '12345678',
        'password_confirmation' => '12345678',
        'rol' => User::ROL_PRODUCTOR,
    ]);
    $responseSinLetras->assertStatus(422);
    $responseSinLetras->assertJsonValidationErrors(['password']);
    expect($responseSinLetras->json('errors.password.0'))->toBe('La contraseña debe contener al menos una letra.');

    // Falla sin números
    $responseSinNumeros = $this->postJson('/api/register', [
        'name' => 'API User',
        'email' => 'api_nonumbers@example.com',
        'password' => 'abcdefgh',
        'password_confirmation' => 'abcdefgh',
        'rol' => User::ROL_PRODUCTOR,
    ]);
    $responseSinNumeros->assertStatus(422);
    $responseSinNumeros->assertJsonValidationErrors(['password']);
    expect($responseSinNumeros->json('errors.password.0'))->toBe('La contraseña debe contener al menos un número.');

    // Pasa con contraseña fuerte
    $responseValida = $this->postJson('/api/register', [
        'name' => 'API User',
        'email' => 'api_strong@example.com',
        'password' => 'agro2026campo',
        'password_confirmation' => 'agro2026campo',
        'rol' => User::ROL_PRODUCTOR,
    ]);
    $responseValida->assertStatus(201);
});

test('mensaje de contraseña comprometida en filtraciones de datos esta en castellano', function () {
    app()->instance(UncompromisedVerifier::class, new class implements UncompromisedVerifier
    {
        public function verify($data)
        {
            return false;
        }
    });

    $validador = Validator::make(
        ['password' => 'agro2026campo'],
        ['password' => [Password::min(8)->letters()->numbers()->uncompromised()]]
    );

    expect($validador->fails())->toBeTrue();
    expect($validador->errors()->first('password'))->toBe('Esta contraseña apareció en una filtración de datos, elegí otra.');
});
