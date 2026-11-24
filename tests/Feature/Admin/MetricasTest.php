<?php

use App\Models\Denuncia;
use App\Models\Publicacion;
use App\Models\Resena;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('un administrador puede consultar las metricas basicas del panel via web', function () {
    $admin = User::factory()->create([
        'rol' => User::ROL_ADMIN,
    ]);

    $autor = User::factory()->create();
    $comprador = User::factory()->create();

    // Crear publicaciones pertenecientes al mismo autor
    $pubActiva1 = Publicacion::factory()->create([
        'usuario_id' => $autor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
    $pubActiva2 = Publicacion::factory()->create([
        'usuario_id' => $autor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
    $pubActiva3 = Publicacion::factory()->create([
        'usuario_id' => $autor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
    Publicacion::factory()->create([
        'usuario_id' => $autor->id,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    // Crear denuncias sobre publicaciones existentes con denunciante existente
    Denuncia::factory()->create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $pubActiva1->id,
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);
    Denuncia::factory()->create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $pubActiva2->id,
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);
    Denuncia::factory()->create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $pubActiva3->id,
        'estado' => Denuncia::ESTADO_REVISADA,
    ]);

    // Crear reseñas sobre publicaciones existentes
    Resena::factory()->create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $pubActiva1->id,
    ]);
    Resena::factory()->create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $pubActiva2->id,
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.resumen'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Metricas/Index')
        ->has('metricas')
        ->where('metricas.total_usuarios', 3)
        ->where('metricas.total_publicaciones_activas', 3)
        ->where('metricas.total_denuncias_pendientes', 2)
        ->where('metricas.total_resenas', 2)
    );
});

test('un administrador puede consultar las metricas en formato json', function () {
    $admin = User::factory()->create([
        'rol' => User::ROL_ADMIN,
    ]);

    $autor = User::factory()->create();
    $comprador = User::factory()->create();

    $pub1 = Publicacion::factory()->create([
        'usuario_id' => $autor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
    $pub2 = Publicacion::factory()->create([
        'usuario_id' => $autor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    Denuncia::factory()->create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $pub1->id,
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);

    Resena::factory()->create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $pub1->id,
    ]);
    Resena::factory()->create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $pub2->id,
    ]);

    $response = $this
        ->actingAs($admin)
        ->getJson(route('admin.resumen'));

    $response->assertOk();
    $response->assertExactJson([
        'total_usuarios' => 3,
        'total_publicaciones_activas' => 2,
        'total_denuncias_pendientes' => 1,
        'total_resenas' => 2,
    ]);
});

test('un usuario no-admin recibe 403 al intentar acceder al endpoint de metricas', function () {
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $comprador = User::factory()->create([
        'rol' => User::ROL_COMPRADOR,
    ]);

    $this->actingAs($productor)
        ->get(route('admin.resumen'))
        ->assertForbidden();

    $this->actingAs($comprador)
        ->get(route('admin.resumen'))
        ->assertForbidden();

    $this->actingAs($productor)
        ->getJson(route('admin.resumen'))
        ->assertForbidden();
});

test('los usuarios invitados no autenticados son redirigidos al login al intentar acceder a metricas', function () {
    $this->get(route('admin.resumen'))
        ->assertRedirect(route('login'));

    $this->getJson(route('admin.resumen'))
        ->assertUnauthorized();
});

// --- KAN-159: socios pendientes y últimas denuncias para el resumen del panel ---

test('el resumen incluye el conteo de solicitudes de socio pendientes', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);

    User::factory()->create(['rol' => User::ROL_PRODUCTOR, 'numero_socio' => 'CPL-1', 'socio_validado' => false]);
    User::factory()->create(['rol' => User::ROL_PRODUCTOR, 'numero_socio' => 'CPL-2', 'socio_validado' => true]);
    User::factory()->create(['rol' => User::ROL_PRODUCTOR, 'numero_socio' => null]);

    $this->actingAs($admin)
        ->get(route('admin.resumen'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('metricas.total_socios_pendientes', 1));
});

test('el resumen muestra las 5 denuncias pendientes mas recientes con su publicacion', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $publicacion = Publicacion::factory()->create(['titulo' => 'Zapallo de prueba']);

    // 6 pendientes (una más vieja que queda afuera) y una revisada que no cuenta.
    $this->travel(-10)->days();
    Denuncia::factory()->create(['estado' => Denuncia::ESTADO_PENDIENTE, 'motivo' => 'La más vieja']);
    $this->travelBack();
    foreach (range(1, 5) as $i) {
        $this->travel($i)->minutes();
        Denuncia::factory()->create([
            'estado' => Denuncia::ESTADO_PENDIENTE,
            'publicacion_id' => $publicacion->id,
            'motivo' => "Motivo {$i}",
        ]);
    }
    Denuncia::factory()->create(['estado' => Denuncia::ESTADO_REVISADA, 'motivo' => 'Ya revisada']);

    $this->actingAs($admin)
        ->get(route('admin.resumen'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('ultimas_denuncias', 5)
            ->where('ultimas_denuncias.0.motivo', 'Motivo 5')
            ->where('ultimas_denuncias.0.publicacion.titulo', 'Zapallo de prueba')
            // Solo viaja lo que se muestra: ni la descripción ni quién denunció.
            ->missing('ultimas_denuncias.0.descripcion')
            ->missing('ultimas_denuncias.0.usuario_id')
        );
});
