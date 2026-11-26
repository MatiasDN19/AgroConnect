<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\SolicitudCompra;
use App\Models\User;

test('un administrador puede exportar socios validados a csv', function () {
    $admin = User::factory()->admin()->create();

    $socioValidado = User::factory()->create([
        'name' => 'Socio Validez',
        'email' => 'socio@agroconnect.test',
        'telefono' => '+543718112233',
        'numero_socio' => 'SOC-999',
        'socio_validado' => true,
    ]);

    $noSocio = User::factory()->create([
        'name' => 'No Socio',
        'email' => 'nosocio@agroconnect.test',
        'socio_validado' => false,
    ]);

    $fecha = now()->format('Y-m-d');

    $response = $this->actingAs($admin)->get(route('admin.exportar.socios'));

    $response->assertOk();
    $response->assertHeader('content-disposition', "attachment; filename=agroconnect-socios-{$fecha}.csv");

    $contenido = $response->streamedContent();

    // Contiene el BOM UTF-8
    expect(str_starts_with($contenido, "\xEF\xBB\xBF"))->toBeTrue();

    // Contiene cabeceras
    expect($contenido)->toContain('Nombre;Email;Teléfono')
        ->toContain('Número de socio')
        ->toContain('Fecha de validación');

    // Contiene socio validado y no el que no es socio
    expect($contenido)->toContain('Socio Validez')
        ->toContain('socio@agroconnect.test')
        ->toContain('+543718112233')
        ->toContain('SOC-999');
    expect($contenido)->not->toContain('nosocio@agroconnect.test');
});

test('un administrador puede exportar usuarios respetando filtros a csv', function () {
    $admin = User::factory()->admin()->create();

    $productor = User::factory()->create([
        'name' => 'Productor Filtrado',
        'email' => 'prod.filtrado@agroconnect.test',
        'rol' => User::ROL_PRODUCTOR,
        'inhabilitado' => false,
        'socio_validado' => true,
    ]);

    $comprador = User::factory()->create([
        'name' => 'Comprador Descartado',
        'email' => 'comp.descartado@agroconnect.test',
        'rol' => User::ROL_COMPRADOR,
        'inhabilitado' => false,
    ]);

    $fecha = now()->format('Y-m-d');

    // Filtrar solo rol productor
    $response = $this->actingAs($admin)->get(route('admin.exportar.usuarios', ['rol' => User::ROL_PRODUCTOR]));

    $response->assertOk();
    $response->assertHeader('content-disposition', "attachment; filename=agroconnect-usuarios-{$fecha}.csv");

    $contenido = $response->streamedContent();
    expect($contenido)->toContain('Nombre;Email;Rol;Estado;Socio')
        ->toContain('Fecha de alta');
    expect($contenido)->toContain('Productor Filtrado')
        ->toContain('prod.filtrado@agroconnect.test')
        ->toContain('Productor;Habilitado;Validado');
    expect($contenido)->not->toContain('comp.descartado@agroconnect.test');
});

test('un administrador puede exportar solicitudes de compra a csv', function () {
    $admin = User::factory()->admin()->create();
    $productor = User::factory()->create(['name' => 'Productor Vendedor', 'rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['name' => 'Comprador Solicitante', 'rol' => User::ROL_COMPRADOR]);

    $categoria = Categoria::factory()->create();
    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'titulo' => 'Banana de Exportación',
        'unidad' => 'kg',
    ]);

    $solicitud = SolicitudCompra::create([
        'usuario_id' => $comprador->id,
        'publicacion_id' => $publicacion->id,
        'cantidad' => 25,
        'estado' => SolicitudCompra::ESTADO_ACEPTADA,
    ]);

    $fecha = now()->format('Y-m-d');

    $response = $this->actingAs($admin)->get(route('admin.exportar.solicitudes'));

    $response->assertOk();
    $response->assertHeader('content-disposition', "attachment; filename=agroconnect-solicitudes-{$fecha}.csv");

    $contenido = $response->streamedContent();
    expect($contenido)->toContain('Fecha;Estado;Publicación;Cantidad;Unidad;Comprador;Productor');
    expect($contenido)->toContain('Aceptada')
        ->toContain('Banana de Exportación')
        ->toContain('25;kg')
        ->toContain('Comprador Solicitante')
        ->toContain('Productor Vendedor');
});

test('la ruta parametrizada /admin/exportar/{tipo} funciona y responde 404 ante tipos desconocidos', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/exportar/socios')->assertOk();
    $this->actingAs($admin)->get('/admin/exportar/usuarios')->assertOk();
    $this->actingAs($admin)->get('/admin/exportar/solicitudes')->assertOk();
    $this->actingAs($admin)->get('/admin/exportar/desconocido')->assertNotFound();
});

test('un usuario no administrador recibe 403 al intentar exportar', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $this->actingAs($productor)->get(route('admin.exportar.socios'))->assertForbidden();
    $this->actingAs($productor)->get(route('admin.exportar.usuarios'))->assertForbidden();
    $this->actingAs($productor)->get(route('admin.exportar.solicitudes'))->assertForbidden();
    $this->actingAs($productor)->get('/admin/exportar/socios')->assertForbidden();
});

test('un invitado es redirigido al login al intentar exportar', function () {
    $this->get(route('admin.exportar.socios'))->assertRedirect(route('login'));
    $this->get(route('admin.exportar.usuarios'))->assertRedirect(route('login'));
    $this->get(route('admin.exportar.solicitudes'))->assertRedirect(route('login'));
});
