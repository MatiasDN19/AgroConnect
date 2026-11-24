<?php

use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

test('sin configuracion guardada en base de datos se usan los valores por defecto', function () {
    Cache::flush();

    // Contacto vacío por defecto: el footer no muestra datos de ejemplo (KAN-225).
    expect(Configuracion::obtener('contacto_email'))->toBeNull();
    expect(Configuracion::obtener('precio_sugerido_minimo'))->toBe(3);
    expect(Configuracion::obtener('fotos_maximo'))->toBe(6);
});

test('un administrador puede acceder a la pantalla de edicion de configuraciones', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.configuracion.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Configuracion/Edit')
        ->has('configuraciones')
    );
});

test('un administrador puede actualizar las configuraciones y se invalida la cache', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);

    $response = $this
        ->actingAs($admin)
        ->put(route('admin.configuracion.update'), [
            'contacto_email' => 'nuevo_soporte@agroconnect.test',
            'contacto_whatsapp' => '+54 9 3718 999999',
            'contacto_direccion' => 'Nueva sede Consorcio Laguna Naineck',
            'red_facebook' => 'https://facebook.com/nueva_pagina',
            'red_instagram' => 'https://instagram.com/nueva_pagina',
            'precio_sugerido_minimo' => 5,
            'fotos_maximo' => 8,
            'fotos_tamano_max_kb' => 4096,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.configuracion.edit'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('configuraciones', [
        'clave' => 'contacto_email',
        'valor' => 'nuevo_soporte@agroconnect.test',
    ]);
    $this->assertDatabaseHas('configuraciones', [
        'clave' => 'precio_sugerido_minimo',
        'valor' => '5',
    ]);

    expect(Configuracion::obtener('contacto_email'))->toBe('nuevo_soporte@agroconnect.test');
    expect((int) Configuracion::obtener('precio_sugerido_minimo'))->toBe(5);
});

test('los valores invalidos arrojan errores de validacion', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);

    $response = $this
        ->actingAs($admin)
        ->put(route('admin.configuracion.update'), [
            'contacto_email' => 'no-es-un-email',
            'red_facebook' => 'no-es-una-url',
            'precio_sugerido_minimo' => -1,
            'fotos_maximo' => 25, // Máximo permitido es 10
        ]);

    $response->assertSessionHasErrors([
        'contacto_email',
        'red_facebook',
        'precio_sugerido_minimo',
        'fotos_maximo',
    ]);
});

test('cambiar precio_sugerido_minimo cambia el umbral del endpoint de precio sugerido', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $categoria = Categoria::factory()->create();

    // Creamos 2 publicaciones activas con la misma unidad
    Publicacion::factory()->count(2)->create([
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
        'precio' => 1000,
        'unidad' => 'kg',
    ]);

    // Con el default (3 publicaciones mínimas), 2 no alcanzan y debe devolver null
    Configuracion::fijar('precio_sugerido_minimo', 3);

    $responseInicial = $this
        ->actingAs($admin)
        ->getJson(route('categorias.precio-sugerido', $categoria));

    $responseInicial->assertOk()
        ->assertJson([
            'precio_promedio' => null,
            'cantidad' => 0,
        ]);

    // Cambiamos el mínimo a 2
    Configuracion::fijar('precio_sugerido_minimo', 2);

    $responseNuevo = $this
        ->actingAs($admin)
        ->getJson(route('categorias.precio-sugerido', $categoria));

    $responseNuevo->assertOk()
        ->assertJson([
            'precio_promedio' => 1000.0,
            'cantidad' => 2,
        ]);
});

test('un usuario con rol productor o comprador recibe 403 al intentar acceder o editar la configuracion', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    // Productor
    $this->actingAs($productor)
        ->get(route('admin.configuracion.edit'))
        ->assertForbidden();

    $this->actingAs($productor)
        ->put(route('admin.configuracion.update'), ['contacto_email' => 'hacker@agroconnect.test'])
        ->assertForbidden();

    // Comprador
    $this->actingAs($comprador)
        ->get(route('admin.configuracion.edit'))
        ->assertForbidden();

    $this->actingAs($comprador)
        ->put(route('admin.configuracion.update'), ['contacto_email' => 'hacker@agroconnect.test'])
        ->assertForbidden();
});

test('un usuario no autenticado es redirigido al login', function () {
    $this->get(route('admin.configuracion.edit'))
        ->assertRedirect(route('login'));

    $this->put(route('admin.configuracion.update'), ['contacto_email' => 'test@test.com'])
        ->assertRedirect(route('login'));
});
