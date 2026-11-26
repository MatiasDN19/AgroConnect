<?php

use App\Models\Categoria;
use App\Models\ReferenciaMensual;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario no autenticado es redirigido al login al intentar acceder a referencias mensuales', function () {
    $response = $this->get(route('admin.referencias.index'));

    $response->assertRedirect(route('login'));
});

test('un usuario no administrador recibe 403 al intentar acceder a referencias mensuales', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $response = $this->actingAs($productor)->get(route('admin.referencias.index'));

    $response->assertForbidden();
});

test('un administrador puede ver el listado de referencias mensuales filtrado por mes', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $categoria = Categoria::factory()->create(['nombre' => 'Hortalizas']);

    // Referencia de noviembre 2026
    ReferenciaMensual::create([
        'mes' => '2026-11-01',
        'producto' => 'Zapallo Criollo',
        'categoria_id' => $categoria->id,
        'unidad' => 'kg',
        'precio_referencia' => 450.00,
        'cantidad_plan_nutrir' => 800.00,
    ]);

    // Referencia de otro mes (octubre 2026)
    ReferenciaMensual::create([
        'mes' => '2026-10-01',
        'producto' => 'Tomate Perita',
        'categoria_id' => $categoria->id,
        'unidad' => 'cajón',
        'precio_referencia' => 12000.00,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.referencias.index', ['mes' => '2026-11-01']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Referencias/Index')
        ->has('referencias', 1)
        ->where('referencias.0.producto', 'Zapallo Criollo')
        ->where('referencias.0.precio_referencia', '450.00')
        ->where('referencias.0.cantidad_plan_nutrir', '800.00')
        ->where('referencias.0.categoria.nombre', 'Hortalizas')
        ->has('categorias')
        ->where('mes', '2026-11-01')
    );
});

test('un administrador puede crear una referencia mensual válida', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $categoria = Categoria::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.referencias.store'), [
        'mes' => '2026-11-15', // Debe normalizarse al 1 del mes
        'producto' => 'Mandioca',
        'categoria_id' => $categoria->id,
        'unidad' => 'bolsa',
        'precio_referencia' => 5000.00,
        'cantidad_plan_nutrir' => 150.00,
        'nota' => 'Calidad seleccionada',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('referencias_mensuales', [
        'mes' => '2026-11-01',
        'producto' => 'Mandioca',
        'unidad' => 'bolsa',
        'precio_referencia' => '5000.00',
        'cantidad_plan_nutrir' => '150.00',
        'nota' => 'Calidad seleccionada',
    ]);
});

test('la creación valida que exista al menos precio_referencia o cantidad_plan_nutrir', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $categoria = Categoria::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.referencias.store'), [
        'mes' => '2026-11-01',
        'producto' => 'Banana',
        'categoria_id' => $categoria->id,
        'unidad' => 'cajón',
        'precio_referencia' => null,
        'cantidad_plan_nutrir' => null,
    ]);

    $response->assertSessionHasErrors('precio_referencia');
    $this->assertDatabaseEmpty('referencias_mensuales');
});

test('la creación rechaza valores negativos en precio o cantidad', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $categoria = Categoria::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.referencias.store'), [
        'mes' => '2026-11-01',
        'producto' => 'Banana',
        'categoria_id' => $categoria->id,
        'unidad' => 'cajón',
        'precio_referencia' => -100,
        'cantidad_plan_nutrir' => -5,
    ]);

    $response->assertSessionHasErrors(['precio_referencia', 'cantidad_plan_nutrir']);
});

test('no se permite duplicar producto y unidad en el mismo mes', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $categoria = Categoria::factory()->create();

    ReferenciaMensual::create([
        'mes' => '2026-11-01',
        'producto' => 'Batata',
        'categoria_id' => $categoria->id,
        'unidad' => 'kg',
        'precio_referencia' => 300.00,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.referencias.store'), [
        'mes' => '2026-11-01',
        'producto' => 'Batata',
        'categoria_id' => $categoria->id,
        'unidad' => 'kg',
        'precio_referencia' => 350.00,
    ]);

    $response->assertSessionHasErrors('unidad');
});

test('un administrador puede actualizar una referencia mensual existente', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $categoria = Categoria::factory()->create();

    $referencia = ReferenciaMensual::create([
        'mes' => '2026-11-01',
        'producto' => 'Cebolla de verdeo',
        'categoria_id' => $categoria->id,
        'unidad' => 'atado',
        'precio_referencia' => 150.00,
    ]);

    $response = $this->actingAs($admin)->put(route('admin.referencias.update', $referencia), [
        'mes' => '2026-11-01',
        'producto' => 'Cebolla de verdeo',
        'categoria_id' => $categoria->id,
        'unidad' => 'atado',
        'precio_referencia' => 180.00,
        'cantidad_plan_nutrir' => 50.00,
        'nota' => 'Ajuste de precio',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('referencias_mensuales', [
        'id' => $referencia->id,
        'precio_referencia' => '180.00',
        'cantidad_plan_nutrir' => '50.00',
        'nota' => 'Ajuste de precio',
    ]);
});

test('un administrador puede eliminar una referencia mensual', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $categoria = Categoria::factory()->create();

    $referencia = ReferenciaMensual::create([
        'mes' => '2026-11-01',
        'producto' => 'Pomelo',
        'categoria_id' => $categoria->id,
        'unidad' => 'docena',
        'precio_referencia' => 800.00,
    ]);

    $response = $this->actingAs($admin)->delete(route('admin.referencias.destroy', $referencia));

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('referencias_mensuales', [
        'id' => $referencia->id,
    ]);
});

test('copiarMesAnterior copia filas del mes previo sin duplicar las existentes', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $categoria = Categoria::factory()->create();

    // Mes anterior: Octubre 2026
    ReferenciaMensual::create([
        'mes' => '2026-10-01',
        'producto' => 'Zapallo',
        'categoria_id' => $categoria->id,
        'unidad' => 'kg',
        'precio_referencia' => 400.00,
        'cantidad_plan_nutrir' => 500.00,
    ]);
    ReferenciaMensual::create([
        'mes' => '2026-10-01',
        'producto' => 'Banana',
        'categoria_id' => $categoria->id,
        'unidad' => 'cajón',
        'precio_referencia' => 3000.00,
    ]);

    // Mes actual: Noviembre 2026 (ya tiene Zapallo cargado)
    ReferenciaMensual::create([
        'mes' => '2026-11-01',
        'producto' => 'Zapallo',
        'categoria_id' => $categoria->id,
        'unidad' => 'kg',
        'precio_referencia' => 480.00, // Precio ajustado para noviembre
    ]);

    $response = $this->actingAs($admin)->post(route('admin.referencias.copiar-mes-anterior'), [
        'mes' => '2026-11-01',
    ]);

    $response->assertSessionHasNoErrors();

    // Debe seguir habiendo un solo Zapallo en noviembre con su precio original
    $this->assertDatabaseHas('referencias_mensuales', [
        'mes' => '2026-11-01',
        'producto' => 'Zapallo',
        'precio_referencia' => '480.00',
    ]);

    // Debe haberse copiado Banana a noviembre
    $this->assertDatabaseHas('referencias_mensuales', [
        'mes' => '2026-11-01',
        'producto' => 'Banana',
        'precio_referencia' => '3000.00',
    ]);

    // Total en noviembre: 2 filas (Zapallo existente + Banana copiada)
    expect(ReferenciaMensual::where('mes', '2026-11-01')->count())->toBe(2);
});
