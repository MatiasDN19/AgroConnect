<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('el sexto intento de login por api en un minuto responde 429 con codigo y mensaje en castellano', function () {
    $user = User::factory()->create([
        'email' => 'ratelimit_login@test.com',
        'password' => Hash::make('password123'),
        'rol' => User::ROL_PRODUCTOR,
    ]);

    // 5 intentos fallidos
    for ($i = 0; $i < 5; $i++) {
        $response = $this->postJson('/api/login', [
            'email' => 'ratelimit_login@test.com',
            'password' => 'clave-incorrecta',
        ]);
        $response->assertStatus(422);
    }

    // El 6to intento es bloqueado por rate limit
    $responseBloqueada = $this->postJson('/api/login', [
        'email' => 'ratelimit_login@test.com',
        'password' => 'clave-incorrecta',
    ]);

    $responseBloqueada->assertStatus(429);
    $responseBloqueada->assertJsonPath('code', 'demasiados_intentos');
    expect($responseBloqueada->json('message'))->toContain('Demasiados intentos. Probá de nuevo en');
    expect($responseBloqueada->headers->get('Retry-After'))->not->toBeNull();
});

test('el sexto intento de registro por api en un minuto responde 429 con formato uniforme', function () {
    // 5 intentos de registro
    for ($i = 0; $i < 5; $i++) {
        $response = $this->postJson('/api/register', [
            'name' => "Usuario {$i}",
            'email' => "usuario{$i}@ratelimit.test",
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => User::ROL_PRODUCTOR,
        ]);
        $response->assertCreated();
    }

    // El 6to intento desde la misma IP
    $responseBloqueada = $this->postJson('/api/register', [
        'name' => 'Usuario 6',
        'email' => 'usuario6@ratelimit.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $responseBloqueada->assertStatus(429);
    $responseBloqueada->assertJsonPath('code', 'demasiados_intentos');
    expect($responseBloqueada->json('message'))->toContain('Demasiados intentos. Probá de nuevo en');
});

test('el sexto intento de registro web en un minuto responde 429 con mensaje en castellano', function () {
    for ($i = 0; $i < 5; $i++) {
        $response = $this->post('/register', [
            'name' => "Web User {$i}",
            'email' => "webuser{$i}@ratelimit.test",
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => User::ROL_PRODUCTOR,
        ]);
        $response->assertRedirect(route('publicaciones.index', absolute: false));
        // Cerrar sesión entre registros web para permitir nuevo registro
        auth()->logout();
    }

    $responseBloqueada = $this->post('/register', [
        'name' => 'Web User 6',
        'email' => 'webuser6@ratelimit.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $responseBloqueada->assertStatus(429);
    expect($responseBloqueada->getContent())->toContain('Demasiados intentos. Probá de nuevo en');
});

test('el envio de resenas tiene un limite de 10 por minuto por usuario', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $categoria = Categoria::factory()->create();

    // Crear 11 publicaciones distintas para reseñar
    $publicaciones = Publicacion::factory()->count(11)->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $this->actingAs($comprador);

    // 10 envíos permitidos
    for ($i = 0; $i < 10; $i++) {
        $response = $this->post("/publicaciones/{$publicaciones[$i]->id}/resenas", [
            'puntuacion' => 5,
            'comentario' => "Excelente producto {$i}",
        ]);
        $response->assertRedirect();
    }

    // El 11vo envío es bloqueado con 429
    $responseBloqueada = $this->post("/publicaciones/{$publicaciones[10]->id}/resenas", [
        'puntuacion' => 5,
        'comentario' => 'Excelente producto 11',
    ]);

    $responseBloqueada->assertStatus(429);
    expect($responseBloqueada->getContent())->toContain('Demasiados intentos');
});

test('el envio de denuncias tiene un limite de 10 por minuto por usuario', function () {
    $usuario = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $publicaciones = Publicacion::factory()->count(11)->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $this->actingAs($usuario);

    for ($i = 0; $i < 10; $i++) {
        $response = $this->post("/publicaciones/{$publicaciones[$i]->id}/denuncias", [
            'motivo' => 'contenido_inapropiado',
            'comentario' => "Denuncia {$i}",
        ]);
        $response->assertRedirect();
    }

    $responseBloqueada = $this->post("/publicaciones/{$publicaciones[10]->id}/denuncias", [
        'motivo' => 'contenido_inapropiado',
        'comentario' => 'Denuncia 11',
    ]);

    $responseBloqueada->assertStatus(429);
    expect($responseBloqueada->getContent())->toContain('Demasiados intentos');
});

test('el envio de solicitudes de compra tiene un limite de 10 por minuto por usuario', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $publicaciones = Publicacion::factory()->count(11)->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $this->actingAs($comprador);

    for ($i = 0; $i < 10; $i++) {
        $response = $this->post("/publicaciones/{$publicaciones[$i]->id}/solicitudes-compra", [
            'cantidad' => 5,
            'mensaje' => "Quiero comprar {$i}",
        ]);
        $response->assertRedirect();
    }

    $responseBloqueada = $this->post("/publicaciones/{$publicaciones[10]->id}/solicitudes-compra", [
        'cantidad' => 5,
        'mensaje' => 'Quiero comprar 11',
    ]);

    $responseBloqueada->assertStatus(429);
    expect($responseBloqueada->getContent())->toContain('Demasiados intentos');
});

test('el envio de resenas por api tiene un limite de 10 por minuto por usuario', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $categoria = Categoria::factory()->create();

    $publicaciones = Publicacion::factory()->count(11)->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $token = $comprador->createToken('test')->plainTextToken;

    for ($i = 0; $i < 10; $i++) {
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/publicaciones/{$publicaciones[$i]->id}/resenas", [
                'puntuacion' => 5,
                'comentario' => "API Review {$i}",
            ]);
        $response->assertCreated();
    }

    $responseBloqueada = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/publicaciones/{$publicaciones[10]->id}/resenas", [
            'puntuacion' => 5,
            'comentario' => 'API Review 11',
        ]);

    $responseBloqueada->assertStatus(429);
    $responseBloqueada->assertJsonPath('code', 'demasiados_intentos');
    expect($responseBloqueada->json('message'))->toContain('Demasiados intentos. Probá de nuevo en');
});

test('las lecturas no se ven afectadas por el limitador de envios', function () {
    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    for ($i = 0; $i < 15; $i++) {
        $response = $this->get('/publicaciones');
        $response->assertOk();
    }
});
