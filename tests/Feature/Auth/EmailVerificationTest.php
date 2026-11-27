<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use App\Notifications\VerificarEmailNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('publicaciones.index', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('un usuario sin verificar puede navegar pero no publicar en la web', function () {
    $user = User::factory()->unverified()->create(['rol' => User::ROL_PRODUCTOR]);

    // Puede navegar el listado
    $this->actingAs($user)->get('/publicaciones')->assertOk();

    // No puede acceder al formulario de creación
    $this->actingAs($user)->get('/publicaciones/crear')
        ->assertRedirect(route('verification.notice'));

    // No puede enviar una nueva publicación
    $this->actingAs($user)->post('/publicaciones', [
        'titulo' => 'Banana de prueba',
        'descripcion' => 'Descripción de banana',
        'precio' => 1000,
        'unidad' => 'cajon',
        'stock' => 50,
        'categoria_id' => 1,
    ])->assertRedirect(route('verification.notice'));
});

test('un usuario sin verificar no puede solicitar compra resenar ni denunciar en la web', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->unverified()->create(['rol' => User::ROL_COMPRADOR]);
    $categoria = Categoria::factory()->create();

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $this->actingAs($comprador);

    // No puede solicitar compra
    $this->post("/publicaciones/{$publicacion->id}/solicitudes-compra", [
        'cantidad' => 2,
    ])->assertRedirect(route('verification.notice'));

    // No puede reseñar
    $this->post("/publicaciones/{$publicacion->id}/resenas", [
        'puntuacion' => 5,
        'comentario' => 'Muy bueno',
    ])->assertRedirect(route('verification.notice'));

    // No puede denunciar
    $this->post("/publicaciones/{$publicacion->id}/denuncias", [
        'motivo' => 'precio_engañoso',
    ])->assertRedirect(route('verification.notice'));
});

test('en la api un usuario sin verificar recibe 409 con codigo email_no_verificado', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->unverified()->create(['rol' => User::ROL_COMPRADOR]);
    $categoria = Categoria::factory()->create();

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $token = $comprador->createToken('test')->plainTextToken;

    // Reseñar por API
    $resenaResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/publicaciones/{$publicacion->id}/resenas", [
            'puntuacion' => 5,
            'comentario' => 'API reseña',
        ]);
    $resenaResponse->assertStatus(409);
    $resenaResponse->assertJson([
        'code' => 'email_no_verificado',
        'message' => 'Tu correo electrónico no está verificado.',
    ]);

    // Denunciar por API
    $denunciaResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/denuncias', [
            'publicacion_id' => $publicacion->id,
            'motivo' => 'precio_invalido',
        ]);
    $denunciaResponse->assertStatus(409);
    $denunciaResponse->assertJson([
        'code' => 'email_no_verificado',
    ]);
});

test('reenviar correo de verificacion por api responde json', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/email/verification-notification');

    $response->assertOk();
    $response->assertJson([
        'message' => 'Se envió un nuevo enlace de verificación a tu correo.',
    ]);

    Notification::assertSentTo(
        $user,
        VerificarEmailNotification::class
    );
});

test('la notificacion de verificacion incluye la identidad de AgroConnect', function () {
    $user = User::factory()->unverified()->create(['name' => 'Marcos Test']);
    $notificacion = new VerificarEmailNotification;

    $mail = $notificacion->toMail($user);

    expect($mail->subject)->toBe('Verificá tu correo electrónico - AgroConnect');
    expect($mail->greeting)->toBe('¡Hola, Marcos Test!');
    expect($mail->actionText)->toBe('Verificar mi correo');
});
