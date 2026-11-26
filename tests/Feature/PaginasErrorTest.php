<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['app.debug' => false]);
});

test('una url inexistente responde 404 con el componente Inertia Error y status 404 cuando debug esta desactivado', function () {
    $response = $this->get('/url-que-no-existe-en-el-sistema');

    $response->assertStatus(404);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Error')
        ->where('status', 404)
    );
});

test('una ruta de admin solicitada por un comprador responde 403 con el componente Error', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $response = $this->actingAs($comprador)->get('/admin/resumen');

    $response->assertStatus(403);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Error')
        ->where('status', 403)
    );
});

test('un error 500 responde con el componente Error y status 500', function () {
    Route::get('/_test_error_500', function () {
        abort(500, 'Error interno del servidor');
    });

    $response = $this->get('/_test_error_500');

    $response->assertStatus(500);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Error')
        ->where('status', 500)
    );
});

test('un error 503 responde con el componente Error y status 503', function () {
    Route::get('/_test_error_503', function () {
        abort(503, 'Servicio no disponible');
    });

    $response = $this->get('/_test_error_503');

    $response->assertStatus(503);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Error')
        ->where('status', 503)
    );
});

test('un error 419 de sesion vencida vuelve atras con el mensaje flash correspondiente', function () {
    Route::post('/_test_error_419', function () {
        abort(419);
    });

    $response = $this->from('/formulario-origen')->post('/_test_error_419');

    $response->assertRedirect('/formulario-origen');
    $response->assertSessionHas('error', 'La página venció. Probá de nuevo.');
});

test('las llamadas a la api siguen respondiendo en formato json y no con inertia', function () {
    $response = $this->getJson('/api/endpoint-que-no-existe');

    $response->assertStatus(404);
    $response->assertHeader('content-type', 'application/json');
});
