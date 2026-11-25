<?php

use App\Models\Alerta;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario autenticado puede ver solo sus propias alertas via web', function () {
    $usuario = User::factory()->create();
    $otroUsuario = User::factory()->create();

    $alertaPropia1 = Alerta::factory()->create([
        'usuario_id' => $usuario->id,
        'mensaje' => 'Tu alerta propia más vieja',
        'leida' => false,
        'created_at' => now()->subDay(),
    ]);

    $alertaPropia2 = Alerta::factory()->create([
        'usuario_id' => $usuario->id,
        'mensaje' => 'Tu alerta propia más reciente',
        'leida' => false,
        'created_at' => now(),
    ]);

    $alertaAjena = Alerta::factory()->create([
        'usuario_id' => $otroUsuario->id,
        'mensaje' => 'Alerta de otra persona',
    ]);

    $response = $this
        ->actingAs($usuario)
        ->get(route('alertas.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Alertas/Index')
        ->has('alertas', 2)
        ->where('alertas.0.id', $alertaPropia2->id)
        ->where('alertas.1.id', $alertaPropia1->id)
        ->whereNot('alertas.0.id', $alertaAjena->id)
        ->whereNot('alertas.1.id', $alertaAjena->id)
    );
});

test('un usuario autenticado puede ver solo sus propias alertas via json', function () {
    $usuario = User::factory()->create();
    $otroUsuario = User::factory()->create();

    $alertaPropia = Alerta::factory()->create([
        'usuario_id' => $usuario->id,
        'mensaje' => 'Alerta JSON propia',
    ]);

    $alertaAjena = Alerta::factory()->create([
        'usuario_id' => $otroUsuario->id,
        'mensaje' => 'Alerta JSON ajena',
    ]);

    $response = $this
        ->actingAs($usuario)
        ->getJson(route('alertas.index'));

    $response->assertOk();
    $data = collect($response->json());

    expect($data->pluck('id'))->toContain($alertaPropia->id);
    expect($data->pluck('id'))->not->toContain($alertaAjena->id);
});

test('un usuario puede marcar como leida una alerta propia', function () {
    $usuario = User::factory()->create();

    $alerta = Alerta::factory()->create([
        'usuario_id' => $usuario->id,
        'leida' => false,
    ]);

    $response = $this
        ->actingAs($usuario)
        ->patch(route('alertas.marcar-leida', $alerta));

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $alerta->refresh();
    expect($alerta->leida)->toBeTrue();

    $this->assertDatabaseHas('alertas', [
        'id' => $alerta->id,
        'leida' => true,
    ]);
});

test('un usuario puede marcar como leida una alerta propia via json', function () {
    $usuario = User::factory()->create();

    $alerta = Alerta::factory()->create([
        'usuario_id' => $usuario->id,
        'leida' => false,
    ]);

    $response = $this
        ->actingAs($usuario)
        ->patchJson(route('alertas.marcar-leida', $alerta));

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);

    $alerta->refresh();
    expect($alerta->leida)->toBeTrue();
});

test('un usuario no puede marcar como leida una alerta que no le pertenece y recibe 403', function () {
    $usuario = User::factory()->create();
    $otroUsuario = User::factory()->create();

    $alertaAjena = Alerta::factory()->create([
        'usuario_id' => $otroUsuario->id,
        'leida' => false,
    ]);

    $response = $this
        ->actingAs($usuario)
        ->patch(route('alertas.marcar-leida', $alertaAjena));

    $response->assertForbidden();

    $alertaAjena->refresh();
    expect($alertaAjena->leida)->toBeFalse();
});

test('los invitados no autenticados no pueden acceder a alertas ni marcarlas como leidas', function () {
    $alerta = Alerta::factory()->create();

    $this->get(route('alertas.index'))
        ->assertRedirect(route('login'));

    $this->patch(route('alertas.marcar-leida', $alerta))
        ->assertRedirect(route('login'));

    $this->getJson(route('alertas.index'))
        ->assertUnauthorized();

    $this->patchJson(route('alertas.marcar-leida', $alerta))
        ->assertUnauthorized();
});

test('la prop compartida alertas_no_leidas refleja la cantidad correcta de alertas no leidas del usuario', function () {
    $usuario = User::factory()->create();
    $otroUsuario = User::factory()->create();

    // 2 no leídas y 1 leída para este usuario
    Alerta::factory()->count(2)->create([
        'usuario_id' => $usuario->id,
        'leida' => false,
    ]);
    $alertaALeer = Alerta::factory()->create([
        'usuario_id' => $usuario->id,
        'leida' => false,
    ]);
    Alerta::factory()->create([
        'usuario_id' => $usuario->id,
        'leida' => true,
    ]);

    // Alertas no leídas de otro usuario (no deben sumar)
    Alerta::factory()->count(5)->create([
        'usuario_id' => $otroUsuario->id,
        'leida' => false,
    ]);

    // Inicialmente tiene 3 no leídas
    $response = $this->actingAs($usuario)->get(route('publicaciones.index'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('alertas_no_leidas', 3)
    );

    // Al marcar una como leída, la prop compartida baja a 2
    $this->actingAs($usuario)->patch(route('alertas.marcar-leida', $alertaALeer));

    $responsePostLeida = $this->actingAs($usuario)->get(route('publicaciones.index'));
    $responsePostLeida->assertOk();
    $responsePostLeida->assertInertia(fn (Assert $page) => $page
        ->where('alertas_no_leidas', 2)
    );
});

test('la prop compartida alertas_no_leidas es 0 para invitados', function () {
    $response = $this->get(route('login'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('alertas_no_leidas', 0)
    );
});
