<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('los invitados no autenticados son redirigidos al login al intentar crear una publicación', function () {
    $categoria = Categoria::factory()->create();

    $response = $this->post(route('publicaciones.store'), [
        'categoria_id' => $categoria->id,
        'titulo' => 'Tomates frescos',
        'descripcion' => 'Cajón de tomates recién cosechados.',
        'precio' => 1500.50,
        'unidad' => 'cajón',
        'stock' => 10,
    ]);

    $response->assertRedirect(route('login'));
    $this->assertDatabaseEmpty('publicaciones');
});

test('un usuario con rol comprador no puede crear una publicación', function () {
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $categoria = Categoria::factory()->create();

    $payload = [
        'categoria_id' => $categoria->id,
        'titulo' => 'Miel pura',
        'descripcion' => 'Frasco de miel pura de monte.',
        'precio' => 2000,
        'unidad' => 'frasco',
        'stock' => 5,
    ];

    $response = $this->actingAs($comprador)->post(route('publicaciones.store'), $payload);

    $response->assertForbidden();
    $this->assertDatabaseMissing('publicaciones', [
        'titulo' => 'Miel pura',
    ]);
});

test('un administrador no puede crear una publicación desde el flujo de publicaciones', function () {
    $admin = User::factory()->admin()->create();
    $categoria = Categoria::factory()->create();

    $payload = [
        'categoria_id' => $categoria->id,
        'titulo' => 'Zapallos',
        'descripcion' => 'Zapallos plomo de primera calidad.',
        'precio' => 800,
        'unidad' => 'kg',
        'stock' => 50,
    ];

    $response = $this->actingAs($admin)->post(route('publicaciones.store'), $payload);

    $response->assertForbidden();
    $this->assertDatabaseMissing('publicaciones', [
        'titulo' => 'Zapallos',
    ]);
});

test('un productor puede crear una publicación con datos válidos', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $payload = [
        'categoria_id' => $categoria->id,
        'titulo' => 'Bananas de Laguna Naineck',
        'descripcion' => 'Bananas locales de excelente maduración y sabor.',
        'precio' => 1200.00,
        'unidad' => 'docena',
        'stock' => 25,
    ];

    $response = $this->actingAs($productor)->post(route('publicaciones.store'), $payload);

    $response->assertSessionHasNoErrors();

    $publicacion = Publicacion::first();

    expect($publicacion)->not->toBeNull();
    $response->assertRedirect(route('publicaciones.show', $publicacion));
    $response->assertSessionHas('success', 'Publicación creada exitosamente.');

    $this->assertDatabaseHas('publicaciones', [
        'id' => $publicacion->id,
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'titulo' => 'Bananas de Laguna Naineck',
        'precio' => 1200.00,
        'unidad' => 'docena',
        'stock' => 25,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);
});

test('la creación de publicación valida los campos requeridos', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $response = $this->actingAs($productor)->post(route('publicaciones.store'), []);

    $response->assertSessionHasErrors([
        'categoria_id',
        'titulo',
        'descripcion',
        'precio',
        'unidad',
        'stock',
    ]);
    $this->assertDatabaseEmpty('publicaciones');
});

test('la creación de publicación rechaza precio y stock negativos', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $payload = [
        'categoria_id' => $categoria->id,
        'titulo' => 'Sandías',
        'descripcion' => 'Sandías dulces.',
        'precio' => -500,
        'unidad' => 'kg',
        'stock' => -3,
    ];

    $response = $this->actingAs($productor)->post(route('publicaciones.store'), $payload);

    $response->assertSessionHasErrors([
        'precio' => 'El precio no puede ser negativo.',
        'stock' => 'El stock no puede ser negativo.',
    ]);
    $this->assertDatabaseMissing('publicaciones', [
        'titulo' => 'Sandías',
    ]);
});

test('la creación de publicación rechaza categorías inexistentes', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $payload = [
        'categoria_id' => 99999,
        'titulo' => 'Mandarinas',
        'descripcion' => 'Mandarinas de estación.',
        'precio' => 900,
        'unidad' => 'kg',
        'stock' => 15,
    ];

    $response = $this->actingAs($productor)->post(route('publicaciones.store'), $payload);

    $response->assertSessionHasErrors([
        'categoria_id' => 'La categoría seleccionada no existe.',
    ]);
    $this->assertDatabaseEmpty('publicaciones');
});

test('la creación de publicación rechaza estados inválidos', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $payload = [
        'categoria_id' => $categoria->id,
        'titulo' => 'Pomelos',
        'descripcion' => 'Pomelos rosados.',
        'precio' => 1100,
        'unidad' => 'bolsa',
        'stock' => 8,
        'estado' => 'estado_falso',
    ];

    $response = $this->actingAs($productor)->post(route('publicaciones.store'), $payload);

    $response->assertSessionHasErrors(['estado']);
    $this->assertDatabaseEmpty('publicaciones');
});

// --- Edición de Publicaciones (KAN-45) ---

test('los invitados no autenticados son redirigidos al login al intentar editar una publicación', function () {
    $publicacion = Publicacion::factory()->create();

    $response = $this->put(route('publicaciones.update', $publicacion), [
        'titulo' => 'Título modificado',
    ]);

    $response->assertRedirect(route('login'));
});

test('el productor dueño puede editar su publicación', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoriaOriginal = Categoria::factory()->create(['nombre' => 'Frutas']);
    $categoriaNueva = Categoria::factory()->create(['nombre' => 'Cítricos']);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoriaOriginal->id,
        'titulo' => 'Naranjas de ombligo',
        'descripcion' => 'Naranjas dulces.',
        'precio' => 1000,
        'unidad' => 'kg',
        'stock' => 20,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $payload = [
        'categoria_id' => $categoriaNueva->id,
        'titulo' => 'Naranjas de ombligo seleccionadas',
        'descripcion' => 'Naranjas dulces recién cosechadas.',
        'precio' => 1250.50,
        'unidad' => 'kg',
        'stock' => 15,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ];

    $response = $this->actingAs($productor)->put(route('publicaciones.update', $publicacion), $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));
    $response->assertSessionHas('success', 'Publicación actualizada exitosamente.');

    $this->assertDatabaseHas('publicaciones', [
        'id' => $publicacion->id,
        'usuario_id' => $productor->id,
        'categoria_id' => $categoriaNueva->id,
        'titulo' => 'Naranjas de ombligo seleccionadas',
        'precio' => 1250.50,
        'stock' => 15,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);
});

test('otro productor no puede editar una publicación ajena', function () {
    $dueno = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $otroProductor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
        'titulo' => 'Título original',
        'precio' => 500,
    ]);

    $response = $this->actingAs($otroProductor)->put(route('publicaciones.update', $publicacion), [
        'titulo' => 'Intento de cambio ajeno',
        'precio' => 100,
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('publicaciones', [
        'id' => $publicacion->id,
        'titulo' => 'Título original',
        'precio' => 500,
    ]);
});

test('un usuario con rol comprador no puede editar una publicación', function () {
    $dueno = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
        'titulo' => 'Título del productor',
    ]);

    $response = $this->actingAs($comprador)->put(route('publicaciones.update', $publicacion), [
        'titulo' => 'Intento de cambio por comprador',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('publicaciones', [
        'id' => $publicacion->id,
        'titulo' => 'Título del productor',
    ]);
});

test('un administrador puede editar cualquier publicación', function () {
    $admin = User::factory()->admin()->create();
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Publicación original',
        'precio' => 900,
    ]);

    $payload = [
        'titulo' => 'Moderado por Admin',
        'precio' => 950,
    ];

    $response = $this->actingAs($admin)->put(route('publicaciones.update', $publicacion), $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));
    $response->assertSessionHas('success', 'Publicación actualizada exitosamente.');

    $this->assertDatabaseHas('publicaciones', [
        'id' => $publicacion->id,
        'usuario_id' => $productor->id,
        'titulo' => 'Moderado por Admin',
        'precio' => 950,
    ]);
});

test('la edición de publicación valida valores negativos en precio y stock', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    $response = $this->actingAs($productor)->put(route('publicaciones.update', $publicacion), [
        'precio' => -20,
        'stock' => -1,
    ]);

    $response->assertSessionHasErrors(['precio', 'stock']);
});

// --- Cambio de Estado de Publicaciones (KAN-48) ---

test('los invitados no autenticados son redirigidos al login al intentar cambiar el estado de una publicación', function () {
    $publicacion = Publicacion::factory()->create(['estado' => Publicacion::ESTADO_ACTIVA]);

    $response = $this->patch(route('publicaciones.estado', $publicacion), [
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    $response->assertRedirect(route('login'));
    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_ACTIVA);
});

test('el dueño de la publicación puede cambiar el estado a pausada y a vendida', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    // Pausar publicación
    $responsePausar = $this->actingAs($productor)
        ->from(route('publicaciones.show', $publicacion))
        ->patch(route('publicaciones.estado', $publicacion), [
            'estado' => Publicacion::ESTADO_PAUSADA,
        ]);

    $responsePausar->assertSessionHasNoErrors();
    $responsePausar->assertRedirect(route('publicaciones.show', $publicacion));
    $responsePausar->assertSessionHas('success', 'Estado de la publicación actualizado exitosamente.');
    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_PAUSADA);

    // Marcar como vendida
    $responseVendida = $this->actingAs($productor)
        ->from(route('publicaciones.show', $publicacion))
        ->patch(route('publicaciones.estado', $publicacion), [
            'estado' => Publicacion::ESTADO_VENDIDA,
        ]);

    $responseVendida->assertSessionHasNoErrors();
    $responseVendida->assertRedirect(route('publicaciones.show', $publicacion));
    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_VENDIDA);

    // Reactivar publicación
    $responseReactivar = $this->actingAs($productor)
        ->from(route('publicaciones.show', $publicacion))
        ->patch(route('publicaciones.estado', $publicacion), [
            'estado' => Publicacion::ESTADO_ACTIVA,
        ]);

    $responseReactivar->assertSessionHasNoErrors();
    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_ACTIVA);
});

test('un administrador puede cambiar el estado de cualquier publicación', function () {
    $admin = User::factory()->admin()->create();
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($admin)
        ->from(route('publicaciones.show', $publicacion))
        ->patch(route('publicaciones.estado', $publicacion), [
            'estado' => Publicacion::ESTADO_PAUSADA,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));
    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_PAUSADA);
});

test('otro usuario no puede cambiar el estado de una publicación ajena', function () {
    $dueno = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $otroUsuario = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($otroUsuario)
        ->patch(route('publicaciones.estado', $publicacion), [
            'estado' => Publicacion::ESTADO_PAUSADA,
        ]);

    $response->assertForbidden();
    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_ACTIVA);
});

test('un usuario con rol comprador no puede cambiar el estado de una publicación ajena', function () {
    $dueno = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($comprador)
        ->patch(route('publicaciones.estado', $publicacion), [
            'estado' => Publicacion::ESTADO_VENDIDA,
        ]);

    $response->assertForbidden();
    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_ACTIVA);
});

test('el cambio de estado valida el campo estado requerido y valores válidos', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    // Estado requerido
    $responseRequired = $this->actingAs($productor)
        ->patch(route('publicaciones.estado', $publicacion), []);

    $responseRequired->assertSessionHasErrors([
        'estado' => 'El estado es obligatorio.',
    ]);

    // Estado inválido
    $responseInvalid = $this->actingAs($productor)
        ->patch(route('publicaciones.estado', $publicacion), [
            'estado' => 'eliminada',
        ]);

    $responseInvalid->assertSessionHasErrors([
        'estado' => 'El estado seleccionado no es válido.',
    ]);

    expect($publicacion->fresh()->estado)->toBe(Publicacion::ESTADO_ACTIVA);
});

test('los invitados no autenticados son redirigidos al login al intentar ver el listado de publicaciones', function () {
    $response = $this->get(route('publicaciones.index'));

    $response->assertRedirect(route('login'));
});

test('el listado de publicaciones solo muestra publicaciones activas', function () {
    $usuario = User::factory()->create();
    $categoria = Categoria::factory()->create();

    $activa1 = Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'titulo' => 'Tomates activos',
        'estado' => Publicacion::ESTADO_ACTIVA,
        'created_at' => now()->subHour(),
    ]);

    $activa2 = Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'titulo' => 'Lechugas activas',
        'estado' => Publicacion::ESTADO_ACTIVA,
        'created_at' => now(),
    ]);

    $pausada = Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'titulo' => 'Miel pausada',
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    $vendida = Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'titulo' => 'Huevos vendidos',
        'estado' => Publicacion::ESTADO_VENDIDA,
    ]);

    $response = $this->actingAs($usuario)->get(route('publicaciones.index'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Index')
        ->has('publicaciones', 2)
        ->where('publicaciones.0.titulo', $activa2->titulo)
        ->where('publicaciones.1.titulo', $activa1->titulo)
    );
});

test('el listado de publicaciones incluye las relaciones y puntuacion promedio', function () {
    $usuario = User::factory()->create();
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR, 'name' => 'Don Pepe']);
    $categoria = Categoria::factory()->create(['nombre' => 'Verduras']);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'titulo' => 'Zanahorias frescas',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $comprador1 = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $comprador2 = User::factory()->create(['rol' => User::ROL_COMPRADOR]);

    $publicacion->resenas()->create([
        'usuario_id' => $comprador1->id,
        'puntuacion' => 4,
        'comentario' => 'Muy buenas',
    ]);

    $publicacion->resenas()->create([
        'usuario_id' => $comprador2->id,
        'puntuacion' => 5,
        'comentario' => 'Excelentes',
    ]);

    $response = $this->actingAs($usuario)->get(route('publicaciones.index'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Index')
        ->has('publicaciones', 1, fn (Assert $item) => $item
            ->where('id', $publicacion->id)
            ->where('titulo', 'Zanahorias frescas')
            ->where('categoria.id', $categoria->id)
            ->where('categoria.nombre', 'Verduras')
            ->where('usuario.id', $productor->id)
            ->where('usuario.name', 'Don Pepe')
            ->where('puntuacion_promedio', fn ($val) => (float) $val === 4.5)
            ->etc()
        )
    );
});

// --- Detalle de Publicación (KAN-57) ---

test('los invitados no autenticados son redirigidos al login al intentar ver el detalle de una publicación', function () {
    $publicacion = Publicacion::factory()->create();

    $response = $this->get(route('publicaciones.show', $publicacion));

    $response->assertRedirect(route('login'));
});

test('cualquier usuario autenticado puede acceder al detalle de una publicación', function (string $rol) {
    $usuario = User::factory()->create(['rol' => $rol]);
    $publicacion = Publicacion::factory()->create();

    $response = $this->actingAs($usuario)->get(route('publicaciones.show', $publicacion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('Publicaciones/Show'));
})->with([
    'productor' => User::ROL_PRODUCTOR,
    'comprador' => User::ROL_COMPRADOR,
    'admin' => User::ROL_ADMIN,
]);

test('el detalle de publicación devuelve todos los datos esperados, categoría, dueño y reseñas con promedio', function () {
    $usuario = User::factory()->create();
    $productor = User::factory()->create([
        'rol' => User::ROL_PRODUCTOR,
        'name' => 'María Gómez',
        'email' => 'maria@ejemplo.com',
        'telefono' => '3704123456',
    ]);
    $categoria = Categoria::factory()->create(['nombre' => 'Frutas']);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'titulo' => 'Mandarinas Criollas',
        'descripcion' => 'Mandarinas muy dulces cosechadas hoy.',
        'precio' => 1800.50,
        'unidad' => 'docena',
        'stock' => 40,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $comprador1 = User::factory()->create(['rol' => User::ROL_COMPRADOR, 'name' => 'Carlos Comprador']);
    $comprador2 = User::factory()->create(['rol' => User::ROL_COMPRADOR, 'name' => 'Ana Compradora']);

    $publicacion->resenas()->create([
        'usuario_id' => $comprador1->id,
        'puntuacion' => 4,
        'comentario' => 'Muy ricas mandarinas',
    ]);

    $publicacion->resenas()->create([
        'usuario_id' => $comprador2->id,
        'puntuacion' => 5,
        'comentario' => 'Excelente atención y calidad',
    ]);

    $response = $this->actingAs($usuario)->get(route('publicaciones.show', $publicacion));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Show')
        ->where('publicacion.id', $publicacion->id)
        ->where('publicacion.titulo', 'Mandarinas Criollas')
        ->where('publicacion.descripcion', 'Mandarinas muy dulces cosechadas hoy.')
        ->where('publicacion.precio', '1800.50')
        ->where('publicacion.unidad', 'docena')
        ->where('publicacion.stock', 40)
        ->where('publicacion.estado', Publicacion::ESTADO_ACTIVA)
        ->where('publicacion.categoria.id', $categoria->id)
        ->where('publicacion.categoria.nombre', 'Frutas')
        ->where('publicacion.usuario.id', $productor->id)
        ->where('publicacion.usuario.name', 'María Gómez')
        ->where('publicacion.usuario.email', 'maria@ejemplo.com')
        ->where('publicacion.usuario.telefono', '3704123456')
        ->has('publicacion.resenas', 2)
        ->where('publicacion.resenas.0.usuario.name', 'Carlos Comprador')
        ->where('publicacion.resenas.0.puntuacion', 4)
        ->where('publicacion.resenas.0.comentario', 'Muy ricas mandarinas')
        ->where('publicacion.resenas.1.usuario.name', 'Ana Compradora')
        ->where('publicacion.resenas.1.puntuacion', 5)
        ->where('puntuacion_promedio', 4.5)
    );
});

test('el detalle de publicación devuelve puntuacion promedio null cuando no tiene reseñas', function () {
    $usuario = User::factory()->create();
    $publicacion = Publicacion::factory()->create();

    $response = $this->actingAs($usuario)->get(route('publicaciones.show', $publicacion));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Show')
        ->where('publicacion.id', $publicacion->id)
        ->has('publicacion.resenas', 0)
        ->where('puntuacion_promedio', null)
    );
});

// --- Filtro por Categoría en Listado de Publicaciones (KAN-54) ---

test('el listado de publicaciones filtra por categoria_id devolviendo solo las publicaciones de esa categoría', function () {
    $usuario = User::factory()->create();

    $categoriaVerduras = Categoria::factory()->create(['nombre' => 'Verduras']);
    $categoriaFrutas = Categoria::factory()->create(['nombre' => 'Frutas']);

    $publicacionVerdura1 = Publicacion::factory()->create([
        'categoria_id' => $categoriaVerduras->id,
        'titulo' => 'Acelga fresca',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $publicacionVerdura2 = Publicacion::factory()->create([
        'categoria_id' => $categoriaVerduras->id,
        'titulo' => 'Espinaca orgánica',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $publicacionFruta = Publicacion::factory()->create([
        'categoria_id' => $categoriaFrutas->id,
        'titulo' => 'Pomelos amarillos',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($usuario)
        ->get(route('publicaciones.index', ['categoria_id' => $categoriaVerduras->id]));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Index')
        ->where('categoria_id', (string) $categoriaVerduras->id)
        ->has('publicaciones', 2)
        ->where('publicaciones.0.categoria.id', $categoriaVerduras->id)
        ->where('publicaciones.1.categoria.id', $categoriaVerduras->id)
    );
});

test('el listado de publicaciones devuelve todas las activas si no se envía categoria_id', function () {
    $usuario = User::factory()->create();

    $categoriaVerduras = Categoria::factory()->create(['nombre' => 'Verduras']);
    $categoriaFrutas = Categoria::factory()->create(['nombre' => 'Frutas']);

    Publicacion::factory()->create([
        'categoria_id' => $categoriaVerduras->id,
        'titulo' => 'Zanahorias',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    Publicacion::factory()->create([
        'categoria_id' => $categoriaFrutas->id,
        'titulo' => 'Mandarinas',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    Publicacion::factory()->create([
        'categoria_id' => $categoriaVerduras->id,
        'titulo' => 'Zapallitos pausados',
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    $response = $this->actingAs($usuario)->get(route('publicaciones.index'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Index')
        ->where('categoria_id', null)
        ->has('publicaciones', 2)
    );
});

test('el listado de publicaciones devuelve un arreglo vacío si no hay publicaciones para la categoría filtrada', function () {
    $usuario = User::factory()->create();
    $categoriaConPublicacion = Categoria::factory()->create();
    $categoriaVacia = Categoria::factory()->create();

    Publicacion::factory()->create([
        'categoria_id' => $categoriaConPublicacion->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($usuario)
        ->get(route('publicaciones.index', ['categoria_id' => $categoriaVacia->id]));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Index')
        ->where('categoria_id', (string) $categoriaVacia->id)
        ->has('publicaciones', 0)
    );
});
