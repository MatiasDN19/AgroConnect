<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\Resena;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario logueado ve el perfil de un productor con su nombre y sus publicaciones activas', function () {
    $viewer = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $productor = User::factory()->create([
        'name' => 'Carlos Productor',
        'rol' => User::ROL_PRODUCTOR,
    ]);
    $otroProductor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $categoria = Categoria::factory()->create();

    $pubActiva = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
        'created_at' => now()->subMinutes(10),
    ]);
    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);
    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_VENDIDA,
    ]);
    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_BLOQUEADA,
    ]);

    // Publicación de otro productor no debe aparecer
    Publicacion::factory()->create([
        'usuario_id' => $otroProductor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($viewer)->get(route('productores.show', $productor));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Productores/Show')
        ->where('productor.id', $productor->id)
        ->where('productor.name', 'Carlos Productor')
        ->has('publicaciones.data', 1)
        ->where('publicaciones.data.0.id', $pubActiva->id)
    );
});

test('la respuesta no incluye datos privados como email, telefono, lat, lng ni numero_socio', function () {
    $viewer = User::factory()->create();
    $productor = User::factory()->create([
        'name' => 'Ana Productora',
        'email' => 'ana@example.com',
        'telefono' => '+543718123456',
        'lat' => -25.2458123,
        'lng' => -57.7345678,
        'numero_socio' => 'SOC-1234',
        'socio_validado' => true,
        'localidad' => 'Laguna Naineck',
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $response = $this->actingAs($viewer)->get(route('productores.show', $productor));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Productores/Show')
        ->where('productor.id', $productor->id)
        ->where('productor.name', 'Ana Productora')
        ->where('productor.socio_validado', true)
        ->where('productor.localidad', 'Laguna Naineck')
        ->missing('productor.email')
        ->missing('productor.telefono')
        ->missing('productor.lat')
        ->missing('productor.lng')
        ->missing('productor.numero_socio')
    );
});

test('el promedio de reseñas y la cantidad total de reseñas del productor se calculan correctamente', function () {
    $viewer = User::factory()->create();
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $otroProductor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $categoria = Categoria::factory()->create();

    $pub1 = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
    $pub2 = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
    $pubOtra = Publicacion::factory()->create([
        'usuario_id' => $otroProductor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $comprador1 = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $comprador2 = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    // Reseñas en publicaciones del productor
    Resena::factory()->create([
        'publicacion_id' => $pub1->id,
        'usuario_id' => $comprador1->id,
        'puntuacion' => 5,
    ]);
    Resena::factory()->create([
        'publicacion_id' => $pub2->id,
        'usuario_id' => $comprador2->id,
        'puntuacion' => 3,
    ]);

    // Reseña en publicación ajena (no debe influir)
    Resena::factory()->create([
        'publicacion_id' => $pubOtra->id,
        'usuario_id' => $comprador1->id,
        'puntuacion' => 1,
    ]);

    $response = $this->actingAs($viewer)->get(route('productores.show', $productor));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Productores/Show')
        ->where('cantidad_resenas', 2)
        ->where('puntuacion_promedio', fn ($val) => (float) $val === 4.0)
    );
});

test('el perfil de un comprador, de un admin o de un productor inhabilitado responde 404', function () {
    $viewer = User::factory()->create();

    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $admin = User::factory()->admin()->create();
    $inhabilitado = User::factory()->inhabilitado()->create(['rol' => User::ROL_PRODUCTOR]);

    $this->actingAs($viewer)->get(route('productores.show', $comprador))->assertNotFound();
    $this->actingAs($viewer)->get(route('productores.show', $admin))->assertNotFound();
    $this->actingAs($viewer)->get(route('productores.show', $inhabilitado))->assertNotFound();
});

test('un invitado es redirigido al login', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $response = $this->get(route('productores.show', $productor));

    $response->assertRedirect(route('login'));
});
