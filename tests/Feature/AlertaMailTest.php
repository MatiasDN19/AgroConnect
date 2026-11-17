<?php

use App\Mail\NuevaAlertaMail;
use App\Models\Denuncia;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

test('al crear una resena via web se envia un correo de nueva alerta al dueno de la publicacion', function () {
    Mail::fake();

    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Tomates Perita',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this
        ->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), [
            'puntuacion' => 5,
            'comentario' => 'Excelente calidad de tomates.',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));

    Mail::assertSent(NuevaAlertaMail::class, function (NuevaAlertaMail $mail) use ($productor, $publicacion) {
        return $mail->hasTo($productor->email)
            && $mail->alerta->tipo === 'nueva_resena'
            && str_contains($mail->alerta->mensaje, $publicacion->titulo);
    });
});

test('al crear una resena via api se envia un correo de nueva alerta al dueno de la publicacion', function () {
    Mail::fake();

    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Sandías Dulces',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    Sanctum::actingAs($comprador);

    $response = $this->postJson("/api/publicaciones/{$publicacion->id}/resenas", [
        'puntuacion' => 4,
        'comentario' => 'Muy ricas sandías.',
    ]);

    $response->assertCreated();

    Mail::assertSent(NuevaAlertaMail::class, function (NuevaAlertaMail $mail) use ($productor, $publicacion) {
        return $mail->hasTo($productor->email)
            && $mail->alerta->tipo === 'nueva_resena'
            && str_contains($mail->alerta->mensaje, $publicacion->titulo);
    });
});

test('al resolver una denuncia como revisada se envia correo al denunciante y al productor', function () {
    Mail::fake();

    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $denunciante = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Naranjas de Laguna Naineck',
    ]);

    $denuncia = Denuncia::factory()->create([
        'usuario_id' => $denunciante->id,
        'publicacion_id' => $publicacion->id,
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('admin.denuncias.update', $denuncia), [
            'estado' => Denuncia::ESTADO_REVISADA,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('admin.denuncias.index'));

    Mail::assertSent(NuevaAlertaMail::class, function (NuevaAlertaMail $mail) use ($denunciante) {
        return $mail->hasTo($denunciante->email)
            && $mail->alerta->tipo === 'denuncia_resuelta';
    });

    Mail::assertSent(NuevaAlertaMail::class, function (NuevaAlertaMail $mail) use ($productor) {
        return $mail->hasTo($productor->email)
            && $mail->alerta->tipo === 'denuncia_resuelta';
    });

    Mail::assertSent(NuevaAlertaMail::class, 2);
});

test('al resolver una denuncia como descartada se envia correo al denunciante y al productor', function () {
    Mail::fake();

    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $denunciante = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Miel Pura',
    ]);

    $denuncia = Denuncia::factory()->create([
        'usuario_id' => $denunciante->id,
        'publicacion_id' => $publicacion->id,
        'estado' => Denuncia::ESTADO_PENDIENTE,
    ]);

    $response = $this
        ->actingAs($admin)
        ->patch(route('admin.denuncias.update', $denuncia), [
            'estado' => Denuncia::ESTADO_DESCARTADA,
        ]);

    $response->assertSessionHasNoErrors();

    Mail::assertSent(NuevaAlertaMail::class, function (NuevaAlertaMail $mail) use ($denunciante) {
        return $mail->hasTo($denunciante->email)
            && $mail->alerta->tipo === 'denuncia_resuelta';
    });

    Mail::assertSent(NuevaAlertaMail::class, function (NuevaAlertaMail $mail) use ($productor) {
        return $mail->hasTo($productor->email)
            && $mail->alerta->tipo === 'denuncia_resuelta';
    });

    Mail::assertSent(NuevaAlertaMail::class, 2);
});

test('no se envia correo si el usuario tiene desactivadas las notificaciones por mail', function () {
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
        'titulo' => 'Zapallos Criollos',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $this
        ->actingAs($comprador)
        ->post(route('publicaciones.resenas.store', $publicacion), [
            'puntuacion' => 5,
            'comentario' => 'Muy buenos zapallos.',
        ]);

    Mail::assertNotSent(NuevaAlertaMail::class);
});
