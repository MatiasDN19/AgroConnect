<?php

use App\Models\Alerta;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('los endpoints de alertas requieren autenticacion con token', function () {
    $alerta = Alerta::factory()->create();

    $this->getJson('/api/alertas')->assertUnauthorized();
    $this->patchJson("/api/alertas/{$alerta->id}/leida")->assertUnauthorized();
});

test('un usuario autenticado solo ve sus propias alertas ordenadas por fecha reciente', function () {
    $usuario = User::factory()->create();
    $otroUsuario = User::factory()->create();

    Sanctum::actingAs($usuario);

    $alertaAntigua = Alerta::factory()->create([
        'usuario_id' => $usuario->id,
        'tipo' => 'solicitud_compra',
        'mensaje' => 'Solicitud antigua recibida',
        'leida' => true,
        'created_at' => now()->subDay(),
    ]);

    $alertaReciente = Alerta::factory()->create([
        'usuario_id' => $usuario->id,
        'tipo' => 'nueva_publicacion',
        'mensaje' => 'Nueva publicación en tu zona',
        'leida' => false,
        'created_at' => now(),
    ]);

    // Alerta de otro usuario
    $alertaAjena = Alerta::factory()->create([
        'usuario_id' => $otroUsuario->id,
        'mensaje' => 'Alerta confidencial de otro usuario',
    ]);

    $response = $this->getJson('/api/alertas');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');

    // Primera alerta es la más reciente
    $response->assertJsonPath('data.0.id', $alertaReciente->id);
    $response->assertJsonPath('data.0.mensaje', 'Nueva publicación en tu zona');
    $response->assertJsonPath('data.0.leida', false);

    // Segunda alerta es la antigua
    $response->assertJsonPath('data.1.id', $alertaAntigua->id);
    $response->assertJsonPath('data.1.mensaje', 'Solicitud antigua recibida');
    $response->assertJsonPath('data.1.leida', true);

    // No debe figurar la alerta ajena
    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->not->toContain($alertaAjena->id);
});

test('un usuario puede marcar como leida una alerta propia', function () {
    $usuario = User::factory()->create();
    Sanctum::actingAs($usuario);

    $alerta = Alerta::factory()->create([
        'usuario_id' => $usuario->id,
        'leida' => false,
    ]);

    $response = $this->patchJson("/api/alertas/{$alerta->id}/leida");

    $response->assertOk();
    $response->assertJsonPath('data.id', $alerta->id);
    $response->assertJsonPath('data.leida', true);

    $this->assertDatabaseHas('alertas', [
        'id' => $alerta->id,
        'leida' => true,
    ]);
});

test('un usuario no puede marcar como leida una alerta de otro usuario y recibe 403', function () {
    $dueno = User::factory()->create();
    $otroUsuario = User::factory()->create();

    $alerta = Alerta::factory()->create([
        'usuario_id' => $dueno->id,
        'leida' => false,
    ]);

    Sanctum::actingAs($otroUsuario);

    $response = $this->patchJson("/api/alertas/{$alerta->id}/leida");

    $response->assertForbidden();

    $this->assertDatabaseHas('alertas', [
        'id' => $alerta->id,
        'leida' => false,
    ]);
});
