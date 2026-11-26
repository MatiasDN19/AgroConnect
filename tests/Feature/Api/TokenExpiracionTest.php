<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

test('un token con mas de 30 dias de antiguedad esta vencido y responde 401', function () {
    $user = User::factory()->create();
    $token = $user->createToken('dispositivo-movil')->plainTextToken;

    // Simular que el token fue creado hace 31 días (supera los 30 días de expiración)
    DB::table('personal_access_tokens')
        ->where('tokenable_id', $user->id)
        ->update(['created_at' => now()->subDays(31)]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/alertas');

    $response->assertUnauthorized();
});

test('un token emitido hace menos de 30 dias sigue funcionando correctamente', function () {
    $user = User::factory()->create();
    $token = $user->createToken('dispositivo-movil')->plainTextToken;

    // Simular que el token fue creado hace 15 días
    DB::table('personal_access_tokens')
        ->where('tokenable_id', $user->id)
        ->update(['created_at' => now()->subDays(15)]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/alertas');

    $response->assertOk();
});

test('al cambiar la contrasena desde el perfil se revocan todos los tokens api del usuario', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password-actual-123'),
    ]);

    $token1 = $user->createToken('telefono-1')->plainTextToken;
    $token2 = $user->createToken('tablet-2')->plainTextToken;

    expect($user->tokens()->count())->toBe(2);

    $response = $this->actingAs($user)->put(route('password.update'), [
        'current_password' => 'password-actual-123',
        'password' => 'nueva-password-456',
        'password_confirmation' => 'nueva-password-456',
    ]);

    $response->assertSessionHasNoErrors();

    // Los tokens deben haber sido eliminados
    expect($user->tokens()->count())->toBe(0);

    // Cerrar sesión web del test para verificar que el Bearer token ya no funciona
    auth()->logout();
    $this->flushSession();

    // Las peticiones con los tokens antiguos ahora fallan con 401
    $this->withHeader('Authorization', 'Bearer '.$token1)->getJson('/api/alertas')->assertUnauthorized();
    $this->withHeader('Authorization', 'Bearer '.$token2)->getJson('/api/alertas')->assertUnauthorized();
});

test('al restablecer la contrasena via link se revocan todos los tokens api del usuario', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password-vieja-123'),
    ]);

    $token = $user->createToken('dispositivo-perdido')->plainTextToken;
    expect($user->tokens()->count())->toBe(1);

    $resetToken = Password::broker()->createToken($user);

    $response = $this->post(route('password.store'), [
        'token' => $resetToken,
        'email' => $user->email,
        'password' => 'nueva-password-super-segura',
        'password_confirmation' => 'nueva-password-super-segura',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('login'));

    // Los tokens deben haber sido eliminados
    expect($user->tokens()->count())->toBe(0);

    $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/alertas')->assertUnauthorized();
});

test('el comando sanctum prune-expired elimina los tokens vencidos de la base de datos', function () {
    $user = User::factory()->create();
    $user->createToken('token-expirado');

    // Token vencido hace más de 30 días + 24 horas
    DB::table('personal_access_tokens')
        ->where('tokenable_id', $user->id)
        ->update(['created_at' => now()->subDays(35)]);

    expect(DB::table('personal_access_tokens')->count())->toBe(1);

    Artisan::call('sanctum:prune-expired', ['--hours' => 24]);

    expect(DB::table('personal_access_tokens')->count())->toBe(0);
});
