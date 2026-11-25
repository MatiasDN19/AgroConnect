<?php

use App\Mail\NuevaAlertaMail;
use App\Models\Alerta;
use App\Models\Publicacion;
use App\Models\SolicitudCompra;
use App\Models\User;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
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

// --- KAN-116: una sola solicitud pendiente y estado en el detalle ---

function crearSolicitud(User $comprador, Publicacion $publicacion, string $estado): SolicitudCompra
{
    return SolicitudCompra::create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'mensaje' => null,
        'estado' => $estado,
    ]);
}

test('un usuario no puede crear una segunda solicitud mientras tenga una pendiente sobre la misma publicación', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create();
    crearSolicitud($comprador, $publicacion, SolicitudCompra::ESTADO_PENDIENTE);

    $response = $this->actingAs($comprador)
        ->from(route('publicaciones.show', $publicacion))
        ->post(route('solicitudes-compra.store', $publicacion), ['mensaje' => 'De nuevo']);

    $response->assertSessionHasErrors([
        'publicacion' => 'Ya tenés una solicitud pendiente para esta publicación.',
    ]);
    expect(SolicitudCompra::count())->toBe(1);
});

test('un usuario puede volver a solicitar la compra si su solicitud anterior fue rechazada', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create();
    crearSolicitud($comprador, $publicacion, SolicitudCompra::ESTADO_RECHAZADA);

    $this->actingAs($comprador)
        ->from(route('publicaciones.show', $publicacion))
        ->post(route('solicitudes-compra.store', $publicacion), ['mensaje' => 'Otra cantidad'])
        ->assertSessionHasNoErrors();

    expect(SolicitudCompra::where('estado', SolicitudCompra::ESTADO_PENDIENTE)->count())->toBe(1);
});

test('una solicitud pendiente de otro usuario no impide solicitar la misma publicación', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create();
    crearSolicitud(User::factory()->create(), $publicacion, SolicitudCompra::ESTADO_PENDIENTE);

    $this->actingAs($comprador)
        ->from(route('publicaciones.show', $publicacion))
        ->post(route('solicitudes-compra.store', $publicacion))
        ->assertSessionHasNoErrors();

    expect(SolicitudCompra::count())->toBe(2);
});

test('el detalle de publicación devuelve mi_solicitud null si el usuario no solicitó la compra', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create();
    crearSolicitud(User::factory()->create(), $publicacion, SolicitudCompra::ESTADO_PENDIENTE);

    $this->actingAs($comprador)
        ->get(route('publicaciones.show', $publicacion))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('mi_solicitud', null));
});

test('el detalle de publicación devuelve el estado de la última solicitud del usuario', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create();

    $this->travel(-2)->days();
    crearSolicitud($comprador, $publicacion, SolicitudCompra::ESTADO_RECHAZADA);
    $this->travelBack();
    crearSolicitud($comprador, $publicacion, SolicitudCompra::ESTADO_ACEPTADA);

    $this->actingAs($comprador)
        ->get(route('publicaciones.show', $publicacion))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('mi_solicitud.estado', SolicitudCompra::ESTADO_ACEPTADA)
            ->has('mi_solicitud.created_at')
            ->missing('mi_solicitud.mensaje')
        );
});

// --- KAN-151: el contacto del comprador solo se ve si la solicitud fue aceptada ---

test('el listado de recibidas solo incluye el contacto del comprador en las solicitudes aceptadas', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create([
        'rol' => User::ROL_COMPRADOR,
        'email' => 'comprador@agroconnect.test',
        'telefono' => '3704111222',
    ]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    // Mismo comprador con una pendiente y una aceptada: cada una se arma por separado.
    $this->travel(-1)->days();
    $aceptada = crearSolicitud($comprador, $publicacion, SolicitudCompra::ESTADO_ACEPTADA);
    $this->travelBack();
    $pendiente = crearSolicitud($comprador, $publicacion, SolicitudCompra::ESTADO_PENDIENTE);

    $this->actingAs($productor)
        ->get(route('solicitudes-compra.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('solicitudes', 2)
            ->where('solicitudes.0.id', $pendiente->id)
            ->where('solicitudes.0.usuario.name', $comprador->name)
            ->missing('solicitudes.0.usuario.email')
            ->missing('solicitudes.0.usuario.telefono')
            ->where('solicitudes.1.id', $aceptada->id)
            ->where('solicitudes.1.usuario.email', 'comprador@agroconnect.test')
            ->where('solicitudes.1.usuario.telefono', '3704111222')
        );
});

// --- KAN-250: alertas, mail y push al recibir y responder solicitudes de compra ---

test('al crear una solicitud de compra se genera una alerta y correo para el productor dueno de la publicacion', function () {
    Mail::fake();
    Http::fake([
        'https://fcm.googleapis.com/fcm/send' => Http::response(['success' => 1], 200),
    ]);

    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'fcm_token' => 'fcm_token_productor_123',
    ]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Mandarinas Criollas',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($comprador)
        ->from(route('publicaciones.show', $publicacion))
        ->post(route('solicitudes-compra.store', $publicacion), [
            'mensaje' => 'Quiero 10 cajones de mandarinas.',
        ]);

    $response->assertSessionHasNoErrors();

    // El productor recibe la alerta
    $this->assertDatabaseHas('alertas', [
        'usuario_id' => $productor->id,
        'tipo' => 'nueva_solicitud_compra',
        'leida' => false,
    ]);

    // El comprador no debe recibir alerta
    $this->assertDatabaseMissing('alertas', [
        'usuario_id' => $comprador->id,
    ]);

    // Se envía correo al productor
    Mail::assertSent(NuevaAlertaMail::class, function (NuevaAlertaMail $mail) use ($productor, $publicacion) {
        return $mail->hasTo($productor->email)
            && $mail->alerta->tipo === 'nueva_solicitud_compra'
            && str_contains($mail->alerta->mensaje, $publicacion->titulo);
    });

    // Se envía push notification
    Http::assertSent(function (ClientRequest $request) use ($publicacion) {
        return $request->url() === 'https://fcm.googleapis.com/fcm/send'
            && $request['to'] === 'fcm_token_productor_123'
            && str_contains($request['notification']['body'], $publicacion->titulo);
    });
});

test('al aceptar una solicitud de compra se genera una alerta y correo para el comprador', function () {
    Mail::fake();
    Http::fake([
        'https://fcm.googleapis.com/fcm/send' => Http::response(['success' => 1], 200),
    ]);

    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create([
        'rol' => User::ROL_COMPRADOR,
        'fcm_token' => 'fcm_token_comprador_456',
    ]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Pomelos Rosados',
    ]);

    $solicitud = crearSolicitud($comprador, $publicacion, SolicitudCompra::ESTADO_PENDIENTE);

    $response = $this->actingAs($productor)
        ->from(route('solicitudes-compra.index'))
        ->patch(route('solicitudes-compra.resolve', $solicitud), [
            'estado' => SolicitudCompra::ESTADO_ACEPTADA,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('solicitudes-compra.index'));

    // El comprador recibe la alerta
    $this->assertDatabaseHas('alertas', [
        'usuario_id' => $comprador->id,
        'tipo' => 'solicitud_compra_aceptada',
        'leida' => false,
    ]);

    // El productor no debe recibir alerta
    $this->assertDatabaseMissing('alertas', [
        'usuario_id' => $productor->id,
    ]);

    // Se envía correo al comprador
    Mail::assertSent(NuevaAlertaMail::class, function (NuevaAlertaMail $mail) use ($comprador, $publicacion) {
        return $mail->hasTo($comprador->email)
            && $mail->alerta->tipo === 'solicitud_compra_aceptada'
            && str_contains($mail->alerta->mensaje, $publicacion->titulo)
            && str_contains($mail->alerta->mensaje, 'aceptada');
    });

    // Se envía push notification
    Http::assertSent(function (ClientRequest $request) use ($publicacion) {
        return $request->url() === 'https://fcm.googleapis.com/fcm/send'
            && $request['to'] === 'fcm_token_comprador_456'
            && str_contains($request['notification']['body'], $publicacion->titulo);
    });
});

test('al rechazar una solicitud de compra se genera una alerta y correo para el comprador', function () {
    Mail::fake();

    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Batatas Coloradas',
    ]);

    $solicitud = crearSolicitud($comprador, $publicacion, SolicitudCompra::ESTADO_PENDIENTE);

    $response = $this->actingAs($productor)
        ->from(route('solicitudes-compra.index'))
        ->patch(route('solicitudes-compra.resolve', $solicitud), [
            'estado' => SolicitudCompra::ESTADO_RECHAZADA,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('solicitudes-compra.index'));

    $this->assertDatabaseHas('alertas', [
        'usuario_id' => $comprador->id,
        'tipo' => 'solicitud_compra_rechazada',
        'leida' => false,
    ]);

    Mail::assertSent(NuevaAlertaMail::class, function (NuevaAlertaMail $mail) use ($comprador, $publicacion) {
        return $mail->hasTo($comprador->email)
            && $mail->alerta->tipo === 'solicitud_compra_rechazada'
            && str_contains($mail->alerta->mensaje, $publicacion->titulo)
            && str_contains($mail->alerta->mensaje, 'rechazada');
    });
});

test('no se envia correo de solicitud de compra si el usuario desactivo notificaciones por mail', function () {
    if (! Schema::hasColumn('users', 'notificar_por_mail')) {
        $this->markTestSkipped('La columna notificar_por_mail se agrega en KAN-77.');
    }

    Mail::fake();

    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'notificar_por_mail' => false,
    ]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Zapallitos',
    ]);

    $this->actingAs($comprador)
        ->post(route('solicitudes-compra.store', $publicacion), [
            'mensaje' => 'Quiero comprar zapallitos.',
        ]);

    Mail::assertNothingSent();
});
