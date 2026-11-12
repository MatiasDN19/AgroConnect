<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    if (DB::connection() instanceof SQLiteConnection) {
        $pdo = DB::connection()->getPdo();
        $pdo->sqliteCreateFunction('cos', 'cos', 1);
        $pdo->sqliteCreateFunction('sin', 'sin', 1);
        $pdo->sqliteCreateFunction('acos', 'acos', 1);
        $pdo->sqliteCreateFunction('radians', fn ($deg) => deg2rad((float) $deg), 1);
    }
});

test('con lat y lng el listado devuelve las publicaciones ordenadas de más cerca a más lejos', function () {
    $usuario = User::factory()->create();

    // Ubicación de referencia (Laguna Naineck centro)
    $userLat = -25.2458;
    $userLng = -57.7345;

    // Productor cercano (~1 km)
    $productorCercano = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'lat' => -25.2460,
        'lng' => -57.7340,
    ]);

    // Productor a distancia media (~15 km)
    $productorMedio = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'lat' => -25.3500,
        'lng' => -57.7500,
    ]);

    // Productor lejano (~110 km)
    $productorLejano = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'lat' => -26.1850,
        'lng' => -58.1750,
    ]);

    // Productor sin ubicación
    $productorSinUbicacion = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'lat' => null,
        'lng' => null,
    ]);

    $pubMedia = Publicacion::factory()->create([
        'usuario_id' => $productorMedio->id,
        'titulo' => 'Miel del Monte',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $pubCercana = Publicacion::factory()->create([
        'usuario_id' => $productorCercano->id,
        'titulo' => 'Tomates de Naineck',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $pubSinUbicacion = Publicacion::factory()->create([
        'usuario_id' => $productorSinUbicacion->id,
        'titulo' => 'Bananas Locales',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $pubLejana = Publicacion::factory()->create([
        'usuario_id' => $productorLejano->id,
        'titulo' => 'Pomelos de Formosa',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($usuario)->get(route('publicaciones.index', [
        'lat' => $userLat,
        'lng' => $userLng,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Index')
        ->where('lat', (string) $userLat)
        ->where('lng', (string) $userLng)
        ->has('publicaciones', 4)
        ->where('publicaciones.0.id', $pubCercana->id)
        ->where('publicaciones.1.id', $pubMedia->id)
        ->where('publicaciones.2.id', $pubLejana->id)
        ->where('publicaciones.3.id', $pubSinUbicacion->id)
    );
});

test('sin lat ni lng el listado se comporta igual que antes ordenando por fecha de creacion descendente', function () {
    $usuario = User::factory()->create();

    $pub1 = Publicacion::factory()->create([
        'titulo' => 'Primera Publicacion',
        'estado' => Publicacion::ESTADO_ACTIVA,
        'created_at' => now()->subHours(2),
    ]);

    $pub2 = Publicacion::factory()->create([
        'titulo' => 'Segunda Publicacion',
        'estado' => Publicacion::ESTADO_ACTIVA,
        'created_at' => now()->subHour(),
    ]);

    $pub3 = Publicacion::factory()->create([
        'titulo' => 'Tercera Publicacion',
        'estado' => Publicacion::ESTADO_ACTIVA,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($usuario)->get(route('publicaciones.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Index')
        ->where('lat', null)
        ->where('lng', null)
        ->has('publicaciones', 3)
        ->where('publicaciones.0.id', $pub3->id)
        ->where('publicaciones.1.id', $pub2->id)
        ->where('publicaciones.2.id', $pub1->id)
    );
});

test('se puede combinar el orden por cercania con el filtro de categoria', function () {
    $usuario = User::factory()->create();
    $catVerduras = Categoria::factory()->create(['nombre' => 'Verduras']);
    $catFrutas = Categoria::factory()->create(['nombre' => 'Frutas']);

    $userLat = -25.2458;
    $userLng = -57.7345;

    // Productor cercano con frutas (no debería aparecer en filtro de verduras)
    $productorCercano = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'lat' => -25.2460,
        'lng' => -57.7340,
    ]);

    // Productor a distancia media con verduras
    $productorMedio = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'lat' => -25.3500,
        'lng' => -57.7500,
    ]);

    // Productor lejano con verduras
    $productorLejano = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'lat' => -26.1850,
        'lng' => -58.1750,
    ]);

    $pubFrutaCercana = Publicacion::factory()->create([
        'usuario_id' => $productorCercano->id,
        'categoria_id' => $catFrutas->id,
        'titulo' => 'Mandarinas',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $pubVerduraMedia = Publicacion::factory()->create([
        'usuario_id' => $productorMedio->id,
        'categoria_id' => $catVerduras->id,
        'titulo' => 'Lechuga',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $pubVerduraLejana = Publicacion::factory()->create([
        'usuario_id' => $productorLejano->id,
        'categoria_id' => $catVerduras->id,
        'titulo' => 'Zanahorias',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($usuario)->get(route('publicaciones.index', [
        'lat' => $userLat,
        'lng' => $userLng,
        'categoria_id' => $catVerduras->id,
    ]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Index')
        ->where('lat', (string) $userLat)
        ->where('lng', (string) $userLng)
        ->where('categoria_id', (string) $catVerduras->id)
        ->has('publicaciones', 2)
        ->where('publicaciones.0.id', $pubVerduraMedia->id)
        ->where('publicaciones.1.id', $pubVerduraLejana->id)
    );
});
