<?php

use App\Models\Categoria;

test('las respuestas de la web incluyen cabeceras de seguridad owasp', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
});

test('las respuestas de la api incluyen cabeceras de seguridad owasp', function () {
    Categoria::factory()->create();

    $response = $this->getJson('/api/categorias');

    $response->assertOk();
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
});

test('en entorno de desarrollo o peticion no segura no se incluye strict-transport-security', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertHeaderMissing('Strict-Transport-Security');
});

test('en entorno de produccion con https se incluye strict-transport-security', function () {
    app()['env'] = 'production';

    $response = $this->withServerVariables([
        'HTTPS' => 'on',
        'SERVER_PORT' => 443,
    ])->get('https://localhost/');

    $response->assertOk();
    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});
