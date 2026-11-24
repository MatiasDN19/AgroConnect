<?php

use App\Models\Categoria;
use App\Models\Favorito;
use App\Models\Publicacion;
use App\Models\Resena;
use App\Models\User;

test('un usuario sin historial recibe como sugeridas las publicaciones activas mas recientes (fallback)', function () {
    $usuario = User::factory()->create();

    $pubVieja = Publicacion::factory()->create([
        'estado' => Publicacion::ESTADO_ACTIVA,
        'created_at' => now()->subDays(3),
    ]);

    $pubReciente = Publicacion::factory()->create([
        'estado' => Publicacion::ESTADO_ACTIVA,
        'created_at' => now()->subDay(),
    ]);

    $response = $this
        ->actingAs($usuario)
        ->getJson(route('publicaciones.sugeridas'));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('data.0.id', $pubReciente->id);
    $response->assertJsonPath('data.1.id', $pubVieja->id);
});

test('un usuario con historial en una categoria recibe sugeridas principalmente de esa categoria', function () {
    $usuario = User::factory()->create();

    $catFrutas = Categoria::factory()->create(['nombre' => 'Frutas']);
    $catVerduras = Categoria::factory()->create(['nombre' => 'Verduras']);

    // Publicación que el usuario guardó como favorito (en Frutas)
    $pubFavorita = Publicacion::factory()->create([
        'categoria_id' => $catFrutas->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    Favorito::create([
        'usuario_id' => $usuario->id,
        'publicacion_id' => $pubFavorita->id,
    ]);

    // Publicaciones disponibles en el sistema
    $pubSugeridaFruta = Publicacion::factory()->create([
        'categoria_id' => $catFrutas->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $pubOtraVerdura = Publicacion::factory()->create([
        'categoria_id' => $catVerduras->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this
        ->actingAs($usuario)
        ->getJson(route('publicaciones.sugeridas'));

    $response->assertOk();
    $dataIds = collect($response->json('data'))->pluck('id');

    expect($dataIds)->toContain($pubSugeridaFruta->id);
    expect($dataIds)->not->toContain($pubOtraVerdura->id);
});

test('las publicaciones con estado distinto de activa nunca aparecen entre las sugeridas', function () {
    $usuario = User::factory()->create();

    $cat = Categoria::factory()->create();

    $pubFavorita = Publicacion::factory()->create([
        'categoria_id' => $cat->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    Favorito::create([
        'usuario_id' => $usuario->id,
        'publicacion_id' => $pubFavorita->id,
    ]);

    $activa = Publicacion::factory()->create([
        'categoria_id' => $cat->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $pausada = Publicacion::factory()->create([
        'categoria_id' => $cat->id,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    $vendida = Publicacion::factory()->create([
        'categoria_id' => $cat->id,
        'estado' => Publicacion::ESTADO_VENDIDA,
    ]);

    $response = $this
        ->actingAs($usuario)
        ->getJson(route('publicaciones.sugeridas'));

    $response->assertOk();
    $dataIds = collect($response->json('data'))->pluck('id');

    expect($dataIds)->toContain($activa->id);
    expect($dataIds)->not->toContain($pausada->id);
    expect($dataIds)->not->toContain($vendida->id);
});

test('entre publicaciones igualmente relevantes la del productor socio validado aparece primero', function () {
    $usuario = User::factory()->create();

    $cat = Categoria::factory()->create();

    // Historial del usuario vía reseña en esta categoría
    $pubResenada = Publicacion::factory()->create([
        'categoria_id' => $cat->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    Resena::factory()->create([
        'usuario_id' => $usuario->id,
        'publicacion_id' => $pubResenada->id,
    ]);

    // Productor socio validado vs productor no socio
    $productorSocio = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'socio_validado' => true,
    ]);

    $productorNoSocio = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'socio_validado' => false,
    ]);

    // Publicación del socio creada antes (más vieja)
    $pubSocioVieja = Publicacion::factory()->create([
        'usuario_id' => $productorSocio->id,
        'categoria_id' => $cat->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
        'created_at' => now()->subDays(5),
    ]);

    // Publicación del no-socio creada después (más reciente)
    $pubNoSocioNueva = Publicacion::factory()->create([
        'usuario_id' => $productorNoSocio->id,
        'categoria_id' => $cat->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
        'created_at' => now()->subDay(),
    ]);

    $response = $this
        ->actingAs($usuario)
        ->getJson(route('publicaciones.sugeridas'));

    $response->assertOk();
    $data = collect($response->json('data'));

    // Debe aparecer la publicación del socio primero a pesar de ser más vieja
    $indexSocio = $data->search(fn ($item) => $item['id'] === $pubSocioVieja->id);
    $indexNoSocio = $data->search(fn ($item) => $item['id'] === $pubNoSocioNueva->id);

    expect($indexSocio)->not->toBeFalse();
    expect($indexNoSocio)->not->toBeFalse();
    expect($indexSocio)->toBeLessThan($indexNoSocio);
});

test('los invitados no autenticados son redirigidos al login o reciben 401 en sugeridas', function () {
    $this->get(route('publicaciones.sugeridas'))
        ->assertRedirect(route('login'));

    $this->getJson(route('publicaciones.sugeridas'))
        ->assertUnauthorized();
});

// --- KAN-170: sin publicaciones propias y con la portada ---

test('un productor no recibe sus propias publicaciones entre las sugeridas', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
    $ajena = Publicacion::factory()->create(['estado' => Publicacion::ESTADO_ACTIVA]);

    $this->actingAs($productor)
        ->getJson(route('publicaciones.sugeridas'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ajena->id);
});

test('las sugeridas incluyen solo la foto de portada de cada publicacion', function () {
    $usuario = User::factory()->create();
    $publicacion = Publicacion::factory()->create(['estado' => Publicacion::ESTADO_ACTIVA]);

    $publicacion->fotos()->create(['ruta' => 'publicaciones/x/segunda.jpg', 'orden' => 1]);
    $publicacion->fotos()->create(['ruta' => 'publicaciones/x/portada.jpg', 'orden' => 0]);

    $this->actingAs($usuario)
        ->getJson(route('publicaciones.sugeridas'))
        ->assertOk()
        ->assertJsonCount(1, 'data.0.fotos')
        ->assertJsonPath('data.0.fotos.0.ruta', 'publicaciones/x/portada.jpg');
});
