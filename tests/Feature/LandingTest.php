<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('la landing page es accesible publicamente y renderiza el componente landing', function () {
    $response = $this->get(route('landing'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Landing')
    );
});

test('la landing page incluye las estadisticas correctas del marketplace', function () {
    $productor1 = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $productor2 = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    User::factory()->create(['rol' => User::ROL_ADMIN]);

    $categoria1 = Categoria::factory()->create();
    $categoria2 = Categoria::factory()->create();

    // 3 publicaciones activas
    Publicacion::factory()->create([
        'usuario_id' => $productor1->id,
        'categoria_id' => $categoria1->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
    Publicacion::factory()->create([
        'usuario_id' => $productor1->id,
        'categoria_id' => $categoria2->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
    Publicacion::factory()->create([
        'usuario_id' => $productor2->id,
        'categoria_id' => $categoria1->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    // 1 publicacion pausada (no debe sumarse a activas)
    Publicacion::factory()->create([
        'usuario_id' => $productor2->id,
        'categoria_id' => $categoria2->id,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    $response = $this->get(route('landing'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Landing')
        ->where('estadisticas.productores', 2)
        ->where('estadisticas.publicaciones_activas', 3)
        ->where('estadisticas.categorias', 2)
    );
});

test('las estadisticas reflejan cero cuando no hay registros', function () {
    $response = $this->get(route('landing'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Landing')
        ->where('estadisticas.productores', 0)
        ->where('estadisticas.publicaciones_activas', 0)
        ->where('estadisticas.categorias', 0)
    );
});

test('la landing page incluye banderas de autenticacion canLogin y canRegister', function () {
    $response = $this->get(route('landing'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Landing')
        ->has('canLogin')
        ->has('canRegister')
        ->where('canLogin', true)
        ->where('canRegister', true)
    );
});

test('la landing page no expone datos personales de los usuarios', function () {
    User::factory()->create([
        'name' => 'Productor Secreto',
        'email' => 'secreto@lagunanaineck.gob.ar',
        'telefono' => '+543718123456',
        'lat' => -25.2912345,
        'lng' => -57.7212345,
        'rol' => User::ROL_PRODUCTOR,
    ]);

    $response = $this->get(route('landing'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Landing')
        ->missing('users')
        ->missing('usuarios')
        ->missing('publicaciones')
    );

    // Verificar que los datos sensibles no aparecen en la respuesta
    $response->assertDontSee('Productor Secreto');
    $response->assertDontSee('secreto@lagunanaineck.gob.ar');
    $response->assertDontSee('+543718123456');
    $response->assertDontSee('-25.2912345');
    $response->assertDontSee('-57.7212345');
});
