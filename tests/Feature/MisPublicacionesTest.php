<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\Resena;
use App\Models\SolicitudCompra;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('los invitados no autenticados son redirigidos al login al intentar acceder a mis publicaciones', function () {
    $response = $this->get(route('publicaciones.mias'));

    $response->assertRedirect(route('login'));
});

test('un comprador recibe 403 al intentar acceder a mis publicaciones', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $response = $this->actingAs($comprador)->get(route('publicaciones.mias'));

    $response->assertForbidden();
});

test('un productor ve sus publicaciones de todos los estados incluidas pausadas, vendidas y bloqueadas', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $pubActiva = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
        'titulo' => 'Tomates Activos',
        'created_at' => now()->subDays(4),
    ]);

    $pubPausada = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_PAUSADA,
        'titulo' => 'Lechugas Pausadas',
        'created_at' => now()->subDays(3),
    ]);

    $pubVendida = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_VENDIDA,
        'titulo' => 'Zapallos Vendidos',
        'created_at' => now()->subDays(2),
    ]);

    $pubBloqueada = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_BLOQUEADA,
        'motivo_bloqueo' => 'Contenido inapropiado',
        'titulo' => 'Miel Bloqueada',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($productor)->get(route('publicaciones.mias'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Mias')
        ->has('publicaciones.data', 4)
        ->where('publicaciones.data.0.id', $pubBloqueada->id)
        ->where('publicaciones.data.0.motivo_bloqueo', 'Contenido inapropiado')
        ->where('publicaciones.data.1.id', $pubVendida->id)
        ->where('publicaciones.data.2.id', $pubPausada->id)
        ->where('publicaciones.data.3.id', $pubActiva->id)
    );
});

test('un productor no ve las publicaciones de otros productores', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $otroProductor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $miPub = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Mi publicación',
    ]);

    $otraPub = Publicacion::factory()->create([
        'usuario_id' => $otroProductor->id,
        'titulo' => 'Publicación de otro',
    ]);

    $response = $this->actingAs($productor)->get(route('publicaciones.mias'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Mias')
        ->has('publicaciones.data', 1)
        ->where('publicaciones.data.0.id', $miPub->id)
    );
});

test('el filtro de estado devuelve solo las publicaciones en ese estado', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $pubPausada = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_VENDIDA,
    ]);

    $response = $this->actingAs($productor)->get(route('publicaciones.mias', ['estado' => 'pausada']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Mias')
        ->has('publicaciones.data', 1)
        ->where('publicaciones.data.0.id', $pubPausada->id)
        ->where('filtros.estado', 'pausada')
    );
});

test('un estado invalido en el filtro se ignora y devuelve todas las publicaciones', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    $response = $this->actingAs($productor)->get(route('publicaciones.mias', ['estado' => 'invalido']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Mias')
        ->has('publicaciones.data', 2)
        ->where('filtros.estado', null)
    );
});

test('el resumen tiene los conteos correctos de activas, pausadas, vendidas, bloqueadas y solicitudes pendientes', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $otroProductor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    // Publicaciones propias
    $pubActiva = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);
    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_VENDIDA,
    ]);
    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_BLOQUEADA,
    ]);

    // Solicitud pendiente en una publicación propia
    $pubActiva->solicitudesCompra()->create([
        'usuario_id' => $comprador->id,
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);

    // Solicitud ya resuelta no debe sumar a pendientes
    $pubActiva->solicitudesCompra()->create([
        'usuario_id' => $comprador->id,
        'estado' => SolicitudCompra::ESTADO_ACEPTADA,
    ]);

    // Publicación de otro productor con solicitud pendiente (no debe sumar a este resumen)
    $otraPub = Publicacion::factory()->create([
        'usuario_id' => $otroProductor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
    $otraPub->solicitudesCompra()->create([
        'usuario_id' => $comprador->id,
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);

    $response = $this->actingAs($productor)->get(route('publicaciones.mias'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Mias')
        ->where('resumen.activas', 1)
        ->where('resumen.pausadas', 1)
        ->where('resumen.vendidas', 1)
        ->where('resumen.bloqueadas', 1)
        ->where('resumen.solicitudes_pendientes', 1)
    );
});

test('las publicaciones cargan categoria, foto de portada y promedio de resenas', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create(['nombre' => 'Hortalizas']);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $pub = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $pub->fotos()->create([
        'ruta' => 'publicaciones/foto1.jpg',
        'orden' => 1,
    ]);

    Resena::factory()->create([
        'publicacion_id' => $pub->id,
        'usuario_id' => $comprador->id,
        'puntuacion' => 4,
    ]);

    $response = $this->actingAs($productor)->get(route('publicaciones.mias'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Mias')
        ->has('publicaciones.data', 1)
        ->where('publicaciones.data.0.categoria.nombre', 'Hortalizas')
        ->where('publicaciones.data.0.puntuacion_promedio', fn ($promedio) => (float) $promedio === 4.0)
        ->has('publicaciones.data.0.fotos', 1)
    );
});

test('la paginacion devuelve 12 publicaciones por pagina y conserva el query string', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    Publicacion::factory()->count(15)->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    $response = $this->actingAs($productor)->get(route('publicaciones.mias', ['estado' => 'pausada']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Mias')
        ->has('publicaciones.data', 12)
        ->where('publicaciones.total', 15)
        ->where('publicaciones.per_page', 12)
        ->where('publicaciones.current_page', 1)
        ->where('publicaciones.last_page', 2)
        ->where('filtros.estado', 'pausada')
    );
});
