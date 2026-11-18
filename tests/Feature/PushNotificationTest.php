<?php

use App\Models\Denuncia;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

test('un usuario autenticado puede registrar su token fcm mediante la api', function () {
    $usuario = User::factory()->create(['fcm_token' => null]);

    Sanctum::actingAs($usuario);

    $response = $this->postJson('/api/push-token', [
        'fcm_token' => 'sample_fcm_token_123456',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Token FCM registrado exitosamente.',
            'fcm_token' => 'sample_fcm_token_123456',
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $usuario->id,
        'fcm_token' => 'sample_fcm_token_123456',
    ]);
});

test('el registro de token fcm requiere un token valido', function () {
    $usuario = User::factory()->create();

    Sanctum::actingAs($usuario);

    $response = $this->postJson('/api/push-token', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['fcm_token']);
});

test('un usuario no autenticado no puede registrar su token fcm', function () {
    $response = $this->postJson('/api/push-token', [
        'fcm_token' => 'token_sin_auth',
    ]);

    $response->assertUnauthorized();
});

test('si el usuario tiene fcm_token se envia la notificacion push al crearse una alerta por resena', function () {
    Http::fake([
        'https://fcm.googleapis.com/fcm/send' => Http::response(['success' => 1], 200),
    ]);

    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'fcm_token' => 'token_productor_abc',
    ]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Tomates Platenses',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this
        ->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), [
            'puntuacion' => 5,
            'comentario' => 'Calidad excelente.',
        ]);

    $response->assertSessionHasNoErrors();

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://fcm.googleapis.com/fcm/send'
            && $request['to'] === 'token_productor_abc'
            && str_contains($request['notification']['body'], 'Tomates Platenses');
    });
});

test('si el usuario no tiene fcm_token no se envia notificacion push', function () {
    Http::fake();

    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'fcm_token' => null,
    ]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Bananas de Laguna Naineck',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $this
        ->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), [
            'puntuacion' => 5,
            'comentario' => 'Muy buenas.',
        ]);

    Http::assertNothingSent();
});

test('si firebase devuelve error la alerta se crea de todas formas sin romper el flujo', function () {
    Http::fake([
        'https://fcm.googleapis.com/fcm/send' => Http::response(['error' => 'Internal Server Error'], 500),
    ]);

    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'fcm_token' => 'token_productor_invalido',
    ]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Pomelos Rosados',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this
        ->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), [
            'puntuacion' => 5,
            'comentario' => 'Todo bien.',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));

    $this->assertDatabaseHas('alertas', [
        'usuario_id' => $productor->id,
        'tipo' => 'nueva_resena',
        'mensaje' => "Tu publicación 'Pomelos Rosados' recibió una nueva reseña.",
        'leida' => false,
    ]);
});

test('se envia notificacion push al resolver una denuncia a las partes con fcm_token', function () {
    Http::fake([
        'https://fcm.googleapis.com/fcm/send' => Http::response(['success' => 1], 200),
    ]);

    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'fcm_token' => 'fcm_token_productor',
    ]);
    $denunciante = User::factory()->create([
        'rol' => User::ROL_COMPRADOR,
        'fcm_token' => 'fcm_token_denunciante',
    ]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Mandarinas Dulces',
    ]);

    $denuncia = Denuncia::factory()->create([
        'usuario_id' => $denunciante->id,
        'publicacion_id' => $publicacion->id,
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('admin.denuncias.update', $denuncia), [
            'estado' => Denuncia::ESTADO_REVISADA,
        ]);

    $response->assertSessionHasNoErrors();

    // Se debe haber enviado push tanto al denunciante como al productor
    Http::assertSent(function (Request $request) {
        return $request['to'] === 'fcm_token_denunciante';
    });

    Http::assertSent(function (Request $request) {
        return $request['to'] === 'fcm_token_productor';
    });
});
