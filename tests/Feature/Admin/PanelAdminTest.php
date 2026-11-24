<?php

use App\Models\Denuncia;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario invitado recibe admin como null en los datos compartidos de inertia', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('admin', null)
    );
});

test('un productor recibe admin como null en los datos compartidos de inertia', function () {
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
    ]);

    Denuncia::factory()->create([
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);

    $response = $this
        ->actingAs($productor)
        ->get(route('publicaciones.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('admin', null)
    );
});

test('un comprador recibe admin como null en los datos compartidos de inertia', function () {
    $comprador = User::factory()->create([
        'rol' => User::ROL_COMPRADOR,
    ]);

    Denuncia::factory()->create([
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);

    $response = $this
        ->actingAs($comprador)
        ->get(route('publicaciones.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('admin', null)
    );
});

test('un administrador recibe el conteo correcto de denuncias pendientes en los datos compartidos de inertia', function () {
    $admin = User::factory()->create([
        'rol' => User::ROL_ADMIN,
    ]);

    // 3 denuncias pendientes
    Denuncia::factory()->count(3)->create([
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);

    // Otras denuncias no pendientes (no deben sumarse al conteo)
    Denuncia::factory()->create([
        'estado' => Denuncia::ESTADO_REVISADA,
    ]);
    Denuncia::factory()->create([
        'estado' => Denuncia::ESTADO_DESCARTADA,
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.resumen'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('admin.denuncias_pendientes', 3)
    );
});

test('el conteo de denuncias pendientes para el administrador es cero cuando no existen pendientes', function () {
    $admin = User::factory()->create([
        'rol' => User::ROL_ADMIN,
    ]);

    Denuncia::factory()->create([
        'estado' => Denuncia::ESTADO_REVISADA,
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.resumen'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('admin.denuncias_pendientes', 0)
    );
});
