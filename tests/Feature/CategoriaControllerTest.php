<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// --- Control de Acceso (Bloqueo a usuarios no administradores y no autenticados) ---

test('los invitados no autenticados son redirigidos al login al intentar acceder a rutas de categorías', function () {
    $categoria = Categoria::factory()->create();

    $this->get(route('categorias.index'))->assertRedirect(route('login'));
    $this->post(route('categorias.store'), ['nombre' => 'Frutas'])->assertRedirect(route('login'));
    $this->put(route('categorias.update', $categoria), ['nombre' => 'Nuevo Nombre'])->assertRedirect(route('login'));
    $this->delete(route('categorias.destroy', $categoria))->assertRedirect(route('login'));
});

test('los usuarios con rol productor no pueden acceder a las rutas de categorías', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $this->actingAs($productor)->get(route('categorias.index'))->assertForbidden();
    $this->actingAs($productor)->post(route('categorias.store'), ['nombre' => 'Frutas'])->assertForbidden();
    $this->actingAs($productor)->put(route('categorias.update', $categoria), ['nombre' => 'Nuevo Nombre'])->assertForbidden();
    $this->actingAs($productor)->delete(route('categorias.destroy', $categoria))->assertForbidden();
});

test('los usuarios con rol comprador no pueden acceder a las rutas de categorías', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $categoria = Categoria::factory()->create();

    $this->actingAs($comprador)->get(route('categorias.index'))->assertForbidden();
    $this->actingAs($comprador)->post(route('categorias.store'), ['nombre' => 'Frutas'])->assertForbidden();
    $this->actingAs($comprador)->put(route('categorias.update', $categoria), ['nombre' => 'Nuevo Nombre'])->assertForbidden();
    $this->actingAs($comprador)->delete(route('categorias.destroy', $categoria))->assertForbidden();
});

// --- Operaciones CRUD para el Administrador ---

test('el administrador puede ver el listado de categorías', function () {
    $admin = User::factory()->admin()->create();
    $categoria = Categoria::factory()->create(['nombre' => 'Verduras']);

    $response = $this->actingAs($admin)->get(route('categorias.index'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Categorias/Index')
            ->has('categorias', 1)
            ->where('categorias.0.id', $categoria->id)
            ->where('categorias.0.nombre', 'Verduras')
            ->has('categorias.0.publicaciones_count')
        );
});

test('el administrador puede crear una categoría válida', function () {
    $admin = User::factory()->admin()->create();

    $payload = [
        'nombre' => 'Hortalizas',
        'descripcion' => 'Productos frescos de la huerta.',
    ];

    $response = $this->actingAs($admin)->post(route('categorias.store'), $payload);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('categorias.index'))
        ->assertSessionHas('success', 'Categoría creada exitosamente.');

    $this->assertDatabaseHas('categorias', [
        'nombre' => 'Hortalizas',
        'descripcion' => 'Productos frescos de la huerta.',
    ]);
});

test('la creación de categoría valida campos requeridos y unicidad del nombre', function () {
    $admin = User::factory()->admin()->create();
    Categoria::factory()->create(['nombre' => 'Frutas']);

    $response = $this->actingAs($admin)->post(route('categorias.store'), [
        'nombre' => '',
        'descripcion' => str_repeat('a', 1001),
    ]);

    $response->assertSessionHasErrors(['nombre', 'descripcion']);

    $responseDuplicado = $this->actingAs($admin)->post(route('categorias.store'), [
        'nombre' => 'Frutas',
    ]);

    $responseDuplicado->assertSessionHasErrors(['nombre']);
});

test('el administrador puede actualizar una categoría existente', function () {
    $admin = User::factory()->admin()->create();
    $categoria = Categoria::factory()->create([
        'nombre' => 'Legumbres Antiguas',
        'descripcion' => 'Descripción anterior',
    ]);

    $response = $this->actingAs($admin)->put(route('categorias.update', $categoria), [
        'nombre' => 'Legumbres Modificadas',
        'descripcion' => 'Descripción actualizada',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('categorias.index'))
        ->assertSessionHas('success', 'Categoría actualizada exitosamente.');

    $this->assertDatabaseHas('categorias', [
        'id' => $categoria->id,
        'nombre' => 'Legumbres Modificadas',
        'descripcion' => 'Descripción actualizada',
    ]);
});

test('la actualización de categoría permite mantener su propio nombre y rechaza nombres duplicados de otras', function () {
    $admin = User::factory()->admin()->create();
    $categoria1 = Categoria::factory()->create(['nombre' => 'Lácteos']);
    $categoria2 = Categoria::factory()->create(['nombre' => 'Carnes']);

    // Mantener su propio nombre debe ser válido
    $responsePropio = $this->actingAs($admin)->put(route('categorias.update', $categoria1), [
        'nombre' => 'Lácteos',
        'descripcion' => 'Nueva descripción',
    ]);
    $responsePropio->assertSessionHasNoErrors();

    // Usar el nombre de otra categoría existente debe fallar
    $responseDuplicado = $this->actingAs($admin)->put(route('categorias.update', $categoria1), [
        'nombre' => 'Carnes',
    ]);
    $responseDuplicado->assertSessionHasErrors(['nombre']);
});

test('el administrador puede eliminar una categoría sin publicaciones asociadas', function () {
    $admin = User::factory()->admin()->create();
    $categoria = Categoria::factory()->create(['nombre' => 'Miel y conservas']);

    $response = $this->actingAs($admin)->delete(route('categorias.destroy', $categoria));

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('categorias.index'))
        ->assertSessionHas('success', 'Categoría eliminada exitosamente.');

    $this->assertDatabaseMissing('categorias', [
        'id' => $categoria->id,
    ]);
});

test('no se puede eliminar una categoría si tiene publicaciones asociadas', function () {
    $admin = User::factory()->admin()->create();
    $categoria = Categoria::factory()->create();
    Publicacion::factory()->create(['categoria_id' => $categoria->id]);

    $response = $this->actingAs($admin)->delete(route('categorias.destroy', $categoria));

    $response->assertRedirect(route('categorias.index'))
        ->assertSessionHas('error', 'No se puede eliminar la categoría porque tiene publicaciones asociadas.');

    $this->assertDatabaseHas('categorias', [
        'id' => $categoria->id,
    ]);
});
