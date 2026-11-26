<?php

use App\Models\Categoria;
use App\Models\Favorito;
use App\Models\Publicacion;
use App\Models\Resena;
use App\Models\SolicitudCompra;
use App\Models\User;
use Database\Seeders\DemoSeeder;

test('demoseeder puebla la base de datos con productores, compradores, publicaciones y actividad', function () {
    $this->seed(DemoSeeder::class);

    // 6 productores + 4 compradores = 10 usuarios
    expect(User::where('rol', User::ROL_PRODUCTOR)->count())->toBe(6);
    expect(User::where('rol', User::ROL_COMPRADOR)->count())->toBe(4);

    // Validar datos de productores (localidad, socio, preferencia de mail)
    $donRamon = User::where('email', 'ramon.benitez@agroconnect.test')->first();
    expect($donRamon)->not->toBeNull();
    expect($donRamon->localidad)->toBe('Laguna Naineck');
    expect($donRamon->socio_validado)->toBeTrue();
    expect($donRamon->notificar_por_mail)->toBeTrue();

    // Categorías base
    expect(Categoria::count())->toBeGreaterThanOrEqual(6);

    // Publicaciones creadas en DemoSeeder
    expect(Publicacion::count())->toBe(31);
    expect(Publicacion::where('estado', Publicacion::ESTADO_ACTIVA)->count())->toBeGreaterThanOrEqual(25);
    expect(Publicacion::where('estado', Publicacion::ESTADO_PAUSADA)->count())->toBe(2);
    expect(Publicacion::where('estado', Publicacion::ESTADO_VENDIDA)->count())->toBe(2);

    // Actividad generada
    expect(Resena::count())->toBe(9);
    expect(Favorito::count())->toBe(8);
    expect(SolicitudCompra::count())->toBe(5);
    expect(SolicitudCompra::where('estado', SolicitudCompra::ESTADO_PENDIENTE)->count())->toBe(2);
    expect(SolicitudCompra::where('estado', SolicitudCompra::ESTADO_ACEPTADA)->count())->toBe(2);
    expect(SolicitudCompra::where('estado', SolicitudCompra::ESTADO_RECHAZADA)->count())->toBe(1);
});

test('demoseeder es idempotente y puede ejecutarse dos veces sin duplicar datos', function () {
    $this->seed(DemoSeeder::class);
    $conteoUsuarios = User::count();
    $conteoPublicaciones = Publicacion::count();
    $conteoResenas = Resena::count();
    $conteoFavoritos = Favorito::count();
    $conteoSolicitudes = SolicitudCompra::count();

    // Ejecutar por segunda vez
    $this->seed(DemoSeeder::class);

    expect(User::count())->toBe($conteoUsuarios);
    expect(Publicacion::count())->toBe($conteoPublicaciones);
    expect(Resena::count())->toBe($conteoResenas);
    expect(Favorito::count())->toBe($conteoFavoritos);
    expect(SolicitudCompra::count())->toBe($conteoSolicitudes);
});
