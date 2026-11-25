<?php

use App\Models\Publicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('un admin ve el listado paginado con los datos esperados y el conteo de publicaciones', function () {
    $admin = User::factory()->admin()->create([
        'created_at' => now()->subDays(2),
    ]);

    $usuario1 = User::factory()->create([
        'name' => 'Juan Perez',
        'email' => 'juan@example.com',
        'rol' => User::ROL_PRODUCTOR,
        'telefono' => '3718123456',
        'socio_validado' => true,
        'inhabilitado' => false,
        'created_at' => now()->subDay(),
    ]);

    Publicacion::factory()->count(3)->create([
        'usuario_id' => $usuario1->id,
    ]);

    $usuario2 = User::factory()->create([
        'name' => 'Maria Gomez',
        'email' => 'maria@example.com',
        'rol' => User::ROL_COMPRADOR,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.usuarios.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Usuarios/Index')
        ->has('usuarios.data', 3) // usuario2 + usuario1 + admin
        ->where('usuarios.data.0.id', $usuario2->id)
        ->where('usuarios.data.1.id', $usuario1->id)
        ->where('usuarios.data.1.name', 'Juan Perez')
        ->where('usuarios.data.1.email', 'juan@example.com')
        ->where('usuarios.data.1.rol', User::ROL_PRODUCTOR)
        ->where('usuarios.data.1.telefono', '3718123456')
        ->where('usuarios.data.1.socio_validado', true)
        ->where('usuarios.data.1.inhabilitado', false)
        ->where('usuarios.data.1.publicaciones_count', 3)
        ->has('filtros')
    );
});

test('la busqueda q encuentra por nombre y por email', function () {
    $admin = User::factory()->admin()->create();

    $usuarioNom = User::factory()->create([
        'name' => 'Carlos Naineck',
        'email' => 'carlos@otrodominio.com',
        'created_at' => now()->subHour(),
    ]);

    $usuarioMail = User::factory()->create([
        'name' => 'Pedro Garcia',
        'email' => 'naineck_pedro@example.com',
        'created_at' => now(),
    ]);

    $usuarioOtro = User::factory()->create([
        'name' => 'Ana Lopez',
        'email' => 'ana@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.usuarios.index', ['q' => 'naineck']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Usuarios/Index')
        ->has('usuarios.data', 2)
        ->where('filtros.q', 'naineck')
        ->where('usuarios.data.0.id', $usuarioMail->id)
        ->where('usuarios.data.1.id', $usuarioNom->id)
    );
});

test('los filtros rol e inhabilitado devuelven solo los usuarios que corresponden', function () {
    $admin = User::factory()->admin()->create();

    $productorActivo = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'inhabilitado' => false,
    ]);

    $productorInhabilitado = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'inhabilitado' => true,
    ]);

    $compradorInhabilitado = User::factory()->create([
        'rol' => User::ROL_COMPRADOR,
        'inhabilitado' => true,
    ]);

    // Filtrar por rol productor
    $resRol = $this->actingAs($admin)->get(route('admin.usuarios.index', ['rol' => User::ROL_PRODUCTOR]));
    $resRol->assertOk();
    $resRol->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Usuarios/Index')
        ->has('usuarios.data', 2)
        ->where('filtros.rol', User::ROL_PRODUCTOR)
    );

    // Filtrar por rol productor e inhabilitado = 1
    $resAmbos = $this->actingAs($admin)->get(route('admin.usuarios.index', [
        'rol' => User::ROL_PRODUCTOR,
        'inhabilitado' => '1',
    ]));
    $resAmbos->assertOk();
    $resAmbos->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Usuarios/Index')
        ->has('usuarios.data', 1)
        ->where('usuarios.data.0.id', $productorInhabilitado->id)
        ->where('filtros.inhabilitado', '1')
    );
});

test('la respuesta no incluye la contrasena ni el fcm_token', function () {
    $admin = User::factory()->admin()->create();

    User::factory()->create([
        'fcm_token' => 'fcm_token_super_secreto',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.usuarios.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Usuarios/Index')
        ->missing('usuarios.data.0.password')
        ->missing('usuarios.data.0.fcm_token')
        ->missing('usuarios.data.1.password')
        ->missing('usuarios.data.1.fcm_token')
    );
});

test('un productor o comprador recibe 403 y un invitado es redirigido al login', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $this->get(route('admin.usuarios.index'))
        ->assertRedirect(route('login'));

    $this->actingAs($productor)->get(route('admin.usuarios.index'))
        ->assertForbidden();

    $this->actingAs($comprador)->get(route('admin.usuarios.index'))
        ->assertForbidden();
});
