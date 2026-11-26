<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('un admin puede inhabilitar a otro usuario', function () {
    $admin = User::factory()->admin()->create();
    $otroUsuario = User::factory()->create([
        'inhabilitado' => false,
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('publicaciones.index'))
        ->patch(route('admin.usuarios.inhabilitar', $otroUsuario));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('publicaciones.index'))
        ->assertSessionHas('success', 'Usuario inhabilitado correctamente.');

    $otroUsuario->refresh();

    expect($otroUsuario->inhabilitado)->toBeTrue();
    expect($otroUsuario->estaInhabilitado())->toBeTrue();

    $this->assertDatabaseHas('users', [
        'id' => $otroUsuario->id,
        'inhabilitado' => true,
    ]);
});

test('un admin puede reactivar a un usuario inhabilitado', function () {
    $admin = User::factory()->admin()->create();
    $otroUsuario = User::factory()->inhabilitado()->create();

    expect($otroUsuario->estaInhabilitado())->toBeTrue();

    $response = $this
        ->actingAs($admin)
        ->from(route('publicaciones.index'))
        ->patch(route('admin.usuarios.inhabilitar', $otroUsuario));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('publicaciones.index'))
        ->assertSessionHas('success', 'Usuario reactivado correctamente.');

    $otroUsuario->refresh();

    expect($otroUsuario->inhabilitado)->toBeFalse();
    expect($otroUsuario->estaInhabilitado())->toBeFalse();

    $this->assertDatabaseHas('users', [
        'id' => $otroUsuario->id,
        'inhabilitado' => false,
    ]);
});

test('un usuario inhabilitado no puede iniciar sesion aunque la contrasena sea correcta', function () {
    $usuario = User::factory()->inhabilitado()->create([
        'password' => 'password',
    ]);

    $response = $this->post('/login', [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $this->assertGuest();

    $response->assertSessionHasErrors([
        'email' => 'Tu cuenta fue inhabilitada, contactá al administrador.',
    ]);
});

test('un usuario inhabilitado no puede iniciar sesion via api movil', function () {
    $usuario = User::factory()->inhabilitado()->create([
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'email' => 'Tu cuenta fue inhabilitada, contactá al administrador.',
        ]);
});

test('un admin no puede inhabilitarse a si mismo', function () {
    $admin = User::factory()->admin()->create([
        'inhabilitado' => false,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('admin.usuarios.inhabilitar', $admin));

    $response->assertForbidden();

    $admin->refresh();

    expect($admin->inhabilitado)->toBeFalse();
    expect($admin->estaInhabilitado())->toBeFalse();

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'inhabilitado' => false,
    ]);
});

test('un usuario no-admin recibe 403 al intentar inhabilitar a otro usuario', function () {
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
    ]);
    $otroUsuario = User::factory()->create([
        'inhabilitado' => false,
    ]);

    $response = $this
        ->actingAs($productor)
        ->patch(route('admin.usuarios.inhabilitar', $otroUsuario));

    $response->assertForbidden();

    $otroUsuario->refresh();
    expect($otroUsuario->estaInhabilitado())->toBeFalse();
});

test('los invitados no autenticados son redirigidos al login al intentar inhabilitar', function () {
    $usuario = User::factory()->create([
        'inhabilitado' => false,
    ]);

    $response = $this->patch(route('admin.usuarios.inhabilitar', $usuario));

    $response->assertRedirect(route('login'));

    $usuario->refresh();
    expect($usuario->estaInhabilitado())->toBeFalse();
});

test('un usuario logueado en la web que es inhabilitado es expulsado al realizar su proxima peticion', function () {
    $usuario = User::factory()->inhabilitado()->create();

    $response = $this->actingAs($usuario)->get(route('publicaciones.index'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Tu cuenta fue inhabilitada. Si creés que es un error, escribinos.');
    $this->assertGuest('web');
});

test('un usuario autenticado por token api que es inhabilitado recibe 403 y su token es eliminado', function () {
    $usuario = User::factory()->create();
    $token = $usuario->createToken('dispositivo-movil')->plainTextToken;

    // Se inhabilita al usuario
    $usuario->update(['inhabilitado' => true]);

    $response = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/alertas');

    $response->assertStatus(403);
    $response->assertJson([
        'message' => 'Tu cuenta fue inhabilitada.',
        'code' => 'cuenta_inhabilitada',
    ]);

    expect($usuario->tokens()->count())->toBe(0);
});

test('al inhabilitar a un usuario desde el panel admin se borran sus tokens api y sesiones en base de datos', function () {
    $admin = User::factory()->admin()->create();
    $usuario = User::factory()->create(['inhabilitado' => false]);

    // Crear tokens API
    $usuario->createToken('token-1');
    $usuario->createToken('token-2');
    expect($usuario->tokens()->count())->toBe(2);

    // Crear registro en tabla sessions si existe
    if (Schema::hasTable('sessions')) {
        DB::table('sessions')->insert([
            'id' => 'session-test-id-1',
            'user_id' => $usuario->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestBrowser',
            'payload' => 'payload-test',
            'last_activity' => time(),
        ]);
        expect(DB::table('sessions')->where('user_id', $usuario->id)->count())->toBe(1);
    }

    $response = $this->actingAs($admin)->patch(route('admin.usuarios.inhabilitar', $usuario));

    $response->assertSessionHas('success', 'Usuario inhabilitado correctamente.');

    // Verificar que los tokens fueron eliminados
    expect($usuario->tokens()->count())->toBe(0);

    // Verificar que las sesiones fueron eliminadas
    if (Schema::hasTable('sessions')) {
        expect(DB::table('sessions')->where('user_id', $usuario->id)->count())->toBe(0);
    }
});

test('un usuario habilitado puede seguir navegando normalmente en web y api', function () {
    $usuario = User::factory()->create(['inhabilitado' => false]);

    $responseWeb = $this->actingAs($usuario)->get(route('publicaciones.index'));
    $responseWeb->assertOk();

    $token = $usuario->createToken('token-valido')->plainTextToken;
    $responseApi = $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/alertas');
    $responseApi->assertOk();
});
