<?php

use App\Models\CuentaSocial;
use App\Models\User;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

function mockSocialiteUser(string $provider, array $userData = []): void
{
    $socialUser = Mockery::mock(SocialiteUser::class);
    $socialUser->shouldReceive('getId')->andReturn($userData['id'] ?? 'google-123456');
    $socialUser->shouldReceive('getName')->andReturn($userData['name'] ?? 'Carlos Productor');
    $socialUser->shouldReceive('getNickname')->andReturn($userData['nickname'] ?? null);
    $socialUser->shouldReceive('getEmail')->andReturn($userData['email'] ?? 'carlos@ejemplo.com');

    $providerMock = Mockery::mock(SocialiteProvider::class);
    $providerMock->shouldReceive('user')->andReturn($socialUser);

    Socialite::shouldReceive('driver')->with($provider)->andReturn($providerMock);
}

test('la ruta de redireccion a google redirige al proveedor oauth', function () {
    $providerMock = Mockery::mock(SocialiteProvider::class);
    $providerMock->shouldReceive('redirect')
        ->once()
        ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

    Socialite::shouldReceive('driver')->with('google')->andReturn($providerMock);

    $response = $this->get(route('social.redirect', 'google'));

    $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
});

test('proveedor no soportado responde 404 en redireccion y callback', function () {
    $this->get(route('social.redirect', 'twitter'))->assertNotFound();
    $this->get(route('social.callback', 'twitter'))->assertNotFound();
});

test('primer ingreso crea usuario y cuenta social, autentica y redirige a elegir rol', function () {
    mockSocialiteUser('google', [
        'id' => 'google-first-123',
        'name' => 'Nuevo Usuario',
        'email' => 'nuevo@ejemplo.com',
    ]);

    $response = $this->get(route('social.callback', 'google'));

    $this->assertAuthenticated();
    $response->assertRedirect(route('rol.elegir'));

    $user = User::where('email', 'nuevo@ejemplo.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Nuevo Usuario');

    $this->assertDatabaseHas('cuentas_sociales', [
        'usuario_id' => $user->id,
        'proveedor' => 'google',
        'proveedor_id' => 'google-first-123',
    ]);
});

test('correo existente vincula la cuenta social sin duplicar usuario', function () {
    $existente = User::factory()->create([
        'email' => 'existente@ejemplo.com',
        'rol' => User::ROL_PRODUCTOR,
    ]);

    mockSocialiteUser('google', [
        'id' => 'google-link-456',
        'name' => 'Usuario Existente Google',
        'email' => 'existente@ejemplo.com',
    ]);

    $response = $this->get(route('social.callback', 'google'));

    $this->assertAuthenticatedAs($existente);
    $response->assertRedirect(route('publicaciones.index', absolute: false));

    expect(User::where('email', 'existente@ejemplo.com')->count())->toBe(1);

    $this->assertDatabaseHas('cuentas_sociales', [
        'usuario_id' => $existente->id,
        'proveedor' => 'google',
        'proveedor_id' => 'google-link-456',
    ]);
});

test('usuario que ya tenia la cuenta vinculada inicia sesion directamente', function () {
    $user = User::factory()->create([
        'email' => 'ya_vinculado@ejemplo.com',
    ]);

    CuentaSocial::create([
        'usuario_id' => $user->id,
        'proveedor' => 'google',
        'proveedor_id' => 'google-existing-789',
    ]);

    mockSocialiteUser('google', [
        'id' => 'google-existing-789',
        'email' => 'ya_vinculado@ejemplo.com',
    ]);

    $response = $this->get(route('social.callback', 'google'));

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('publicaciones.index', absolute: false));
});

test('usuario inhabilitado no puede ingresar y es redirigido a login', function () {
    $userInhabilitado = User::factory()->create([
        'email' => 'inhabilitado@ejemplo.com',
        'inhabilitado' => true,
    ]);

    CuentaSocial::create([
        'usuario_id' => $userInhabilitado->id,
        'proveedor' => 'google',
        'proveedor_id' => 'google-banned-000',
    ]);

    mockSocialiteUser('google', [
        'id' => 'google-banned-000',
        'email' => 'inhabilitado@ejemplo.com',
    ]);

    $response = $this->get(route('social.callback', 'google'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors([
        'email' => 'Tu cuenta fue inhabilitada, contactá al administrador.',
    ]);
});

test('si el callback oauth falla redirige a login con error', function () {
    $providerMock = Mockery::mock(SocialiteProvider::class);
    $providerMock->shouldReceive('user')
        ->once()
        ->andThrow(new Exception('Error de comunicación con Google'));

    Socialite::shouldReceive('driver')->with('google')->andReturn($providerMock);

    $response = $this->get(route('social.callback', 'google'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
});

test('si el proveedor no provee email redirige a login con error', function () {
    $socialUser = Mockery::mock(SocialiteUser::class);
    $socialUser->shouldReceive('getId')->andReturn('google-no-email');
    $socialUser->shouldReceive('getEmail')->andReturn(null);

    $providerMock = Mockery::mock(SocialiteProvider::class);
    $providerMock->shouldReceive('user')->andReturn($socialUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($providerMock);

    $response = $this->get(route('social.callback', 'google'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors([
        'email' => 'La cuenta de Google no tiene un correo electrónico asociado.',
    ]);
});

test('usuario autenticado puede elegir su rol y es redirigido a publicaciones', function () {
    $user = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $response = $this->actingAs($user)->post(route('rol.guardar'), [
        'rol' => User::ROL_COMPRADOR,
    ]);

    $response->assertRedirect(route('publicaciones.index', absolute: false));
    expect($user->fresh()->rol)->toBe(User::ROL_COMPRADOR);
});

test('elegir rol requiere un rol valido', function () {
    $user = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $response = $this->actingAs($user)->post(route('rol.guardar'), [
        'rol' => 'rol_invalido',
    ]);

    $response->assertSessionHasErrors('rol');
});
