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
