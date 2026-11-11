<?php

use App\Models\Publicacion;
use App\Models\SolicitudCompra;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('los invitados no autenticados son redirigidos al login al intentar crear una solicitud de compra', function () {
    $publicacion = Publicacion::factory()->create();

    $response = $this->post(route('solicitudes-compra.store', $publicacion), [
        'mensaje' => 'Hola, quiero comprar este producto.',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertDatabaseEmpty('solicitudes_compra');
});

test('un comprador puede crear una solicitud de compra sobre una publicación ajena y queda en estado pendiente', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Miel pura de monte',
    ]);

    $response = $this->actingAs($comprador)
        ->from(route('publicaciones.show', $publicacion))
        ->post(route('solicitudes-compra.store', $publicacion), [
            'mensaje' => 'Hola, ¿tenés disponible para retirar este fin de semana?',
        ]);

    $response->assertRedirect(route('publicaciones.show', $publicacion));
    $response->assertSessionHas('success', 'Solicitud de compra enviada exitosamente.');

    $this->assertDatabaseHas('solicitudes_compra', [
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'mensaje' => 'Hola, ¿tenés disponible para retirar este fin de semana?',
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);
});

test('un usuario no puede crear una solicitud de compra sobre su propia publicación', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Tomates frescos',
    ]);

    $response = $this->actingAs($productor)
        ->from(route('publicaciones.show', $publicacion))
        ->post(route('solicitudes-compra.store', $publicacion), [
            'mensaje' => 'Intentando auto-comprar',
        ]);

    $response->assertSessionHasErrors([
        'publicacion' => 'No podés solicitar la compra de tu propia publicación.',
    ]);

    $this->assertDatabaseEmpty('solicitudes_compra');
});

test('el mensaje es opcional al crear una solicitud de compra', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
    ]);

    $response = $this->actingAs($comprador)
        ->from(route('publicaciones.show', $publicacion))
        ->post(route('solicitudes-compra.store', $publicacion), []);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));

    $this->assertDatabaseHas('solicitudes_compra', [
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'mensaje' => null,
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);
});

// --- Listar y Resolver Solicitudes de Compra Recibidas (KAN-160 / KAN-183) ---

test('los invitados no autenticados son redirigidos al login al intentar listar o resolver solicitudes de compra', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    $solicitud = SolicitudCompra::create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);

    $this->get(route('solicitudes-compra.index'))->assertRedirect(route('login'));
    $this->patch(route('solicitudes-compra.resolve', $solicitud), [
        'estado' => SolicitudCompra::ESTADO_ACEPTADA,
    ])->assertRedirect(route('login'));
});

test('el dueño de la publicación puede aceptar una solicitud de compra pendiente', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    $solicitud = SolicitudCompra::create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'mensaje' => 'Quiero 10 kilos.',
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);

    $response = $this->actingAs($productor)
        ->from(route('solicitudes-compra.index'))
        ->patch(route('solicitudes-compra.resolve', $solicitud), [
            'estado' => SolicitudCompra::ESTADO_ACEPTADA,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('solicitudes-compra.index'));
    $response->assertSessionHas('success', 'Solicitud de compra aceptada exitosamente.');

    expect($solicitud->fresh()->estado)->toBe(SolicitudCompra::ESTADO_ACEPTADA);
});

test('el dueño de la publicación puede rechazar una solicitud de compra pendiente', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    $solicitud = SolicitudCompra::create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'mensaje' => 'Consulta por stock.',
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);

    $response = $this->actingAs($productor)
        ->from(route('solicitudes-compra.index'))
        ->patch(route('solicitudes-compra.resolve', $solicitud), [
            'estado' => SolicitudCompra::ESTADO_RECHAZADA,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('solicitudes-compra.index'));
    $response->assertSessionHas('success', 'Solicitud de compra rechazada exitosamente.');

    expect($solicitud->fresh()->estado)->toBe(SolicitudCompra::ESTADO_RECHAZADA);
});

test('un usuario que no es dueño de la publicación ni admin no puede resolver la solicitud', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $otroUsuario = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    $solicitud = SolicitudCompra::create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);

    $response = $this->actingAs($otroUsuario)
        ->patch(route('solicitudes-compra.resolve', $solicitud), [
            'estado' => SolicitudCompra::ESTADO_ACEPTADA,
        ]);

    $response->assertForbidden();
    expect($solicitud->fresh()->estado)->toBe(SolicitudCompra::ESTADO_PENDIENTE);
});

test('un administrador puede resolver cualquier solicitud de compra', function () {
    $admin = User::factory()->admin()->create();
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    $solicitud = SolicitudCompra::create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('solicitudes-compra.resolve', $solicitud), [
            'estado' => SolicitudCompra::ESTADO_ACEPTADA,
        ]);

    $response->assertSessionHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe(SolicitudCompra::ESTADO_ACEPTADA);
});

test('una solicitud ya resuelta no puede volver a cambiar de estado', function (string $estadoInicial) {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    $solicitud = SolicitudCompra::create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'estado' => $estadoInicial,
    ]);

    $response = $this->actingAs($productor)
        ->patch(route('solicitudes-compra.resolve', $solicitud), [
            'estado' => SolicitudCompra::ESTADO_ACEPTADA,
        ]);

    $response->assertSessionHasErrors([
        'estado' => 'Solo se pueden responder solicitudes en estado pendiente.',
    ]);

    expect($solicitud->fresh()->estado)->toBe($estadoInicial);
})->with([
    'ya aceptada' => SolicitudCompra::ESTADO_ACEPTADA,
    'ya rechazada' => SolicitudCompra::ESTADO_RECHAZADA,
]);

test('el listado de recibidas solo muestra solicitudes de publicaciones del usuario autenticado', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $otroProductor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $miPublicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Mi Cajón de Frutillas',
    ]);

    $otraPublicacion = Publicacion::factory()->create([
        'usuario_id' => $otroProductor->id,
        'titulo' => 'Lechugas de otro',
    ]);

    $solicitudParaMi = SolicitudCompra::create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $miPublicacion->id,
        'mensaje' => 'Quiero 2 cajones',
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);

    SolicitudCompra::create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $otraPublicacion->id,
        'mensaje' => 'Solicitud ajena',
        'estado' => SolicitudCompra::ESTADO_PENDIENTE,
    ]);

    $response = $this->actingAs($productor)->get(route('solicitudes-compra.index'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('SolicitudesCompra/Index')
        ->has('solicitudes', 1)
        ->where('solicitudes.0.id', $solicitudParaMi->id)
        ->where('solicitudes.0.publicacion.titulo', 'Mi Cajón de Frutillas')
        ->where('solicitudes.0.usuario.name', $comprador->name)
    );
});

// --- KAN-183: Visibilidad y datos para WhatsApp ---

test('el detalle de la publicación incluye el teléfono del productor cuando está cargado para habilitar WhatsApp', function () {
    $usuario = User::factory()->create();
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'telefono' => '3704987654',
    ]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
    ]);

    $response = $this->actingAs($usuario)->get(route('publicaciones.show', $publicacion));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Show')
        ->where('publicacion.usuario.telefono', '3704987654')
    );
});

test('el detalle de la publicación devuelve teléfono null cuando el productor no lo tiene cargado', function () {
    $usuario = User::factory()->create();
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'telefono' => null,
    ]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
    ]);

    $response = $this->actingAs($usuario)->get(route('publicaciones.show', $publicacion));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Show')
        ->where('publicacion.usuario.telefono', null)
    );
});
