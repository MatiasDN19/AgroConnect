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

// --- Detalle y Listado de Reseñas de una Publicación (KAN-57 / KAN-61 / KAN-63) ---

test('los invitados no autenticados son redirigidos al login al intentar ver el detalle de una publicación', function () {
    $publicacion = Publicacion::factory()->create();

    $response = $this->get(route('publicaciones.show', $publicacion));

    $response->assertRedirect(route('login'));
});

test('el detalle de una publicación muestra los datos del producto, productor y categoría', function () {
    $productor = User::factory()->create([
        'name' => 'María Gomez',
        'email' => 'maria@agroconnect.test',
        'telefono' => '3704123456',
        'rol' => User::ROL_PRODUCTOR,
    ]);
    $categoria = Categoria::factory()->create(['nombre' => 'Frutas']);
    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
        'titulo' => 'Pomelos rosados',
        'descripcion' => 'Pomelos recién cosechados del lote.',
        'precio' => 1800.50,
        'unidad' => 'cajón',
        'stock' => 12,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('publicaciones.show', $publicacion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Show')
        ->where('publicacion.id', $publicacion->id)
        ->where('publicacion.titulo', 'Pomelos rosados')
        ->where('publicacion.descripcion', 'Pomelos recién cosechados del lote.')
        ->where('publicacion.precio', '1800.50')
        ->where('publicacion.unidad', 'cajón')
        ->where('publicacion.stock', 12)
        ->where('publicacion.estado', Publicacion::ESTADO_ACTIVA)
        ->where('publicacion.categoria.id', $categoria->id)
        ->where('publicacion.categoria.nombre', 'Frutas')
        ->where('publicacion.usuario.id', $productor->id)
        ->where('publicacion.usuario.name', 'María Gomez')
        ->where('publicacion.usuario.email', 'maria@agroconnect.test')
        ->where('publicacion.usuario.telefono', '3704123456')
        ->where('puntuacion_promedio', null)
        ->has('publicacion.resenas', 0)
    );
});

test('el detalle de una publicación lista todas las reseñas con puntuación, comentario, autor y puntuación promedio', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    $comprador1 = User::factory()->create(['name' => 'Carlos López', 'rol' => User::ROL_COMPRADOR]);
    $comprador2 = User::factory()->create(['name' => 'Ana Martínez', 'rol' => User::ROL_COMPRADOR]);

    $publicacion->resenas()->create([
        'usuario_id' => $comprador1->id,
        'puntuacion' => 5,
        'comentario' => 'Calidad impecable y muy buena atención.',
    ]);

    $publicacion->resenas()->create([
        'usuario_id' => $comprador2->id,
        'puntuacion' => 3,
        'comentario' => 'Llegaron bien aunque con un poco de demora.',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('publicaciones.show', $publicacion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Publicaciones/Show')
        ->where('publicacion.id', $publicacion->id)
        ->where('puntuacion_promedio', fn ($val) => (float) $val === 4.0)
        ->has('publicacion.resenas', 2)
        ->has('publicacion.resenas', fn (Assert $resenas) => $resenas
            ->each(fn (Assert $resena) => $resena
                ->has('id')
                ->has('puntuacion')
                ->has('comentario')
                ->has('usuario.id')
                ->has('usuario.name')
                ->etc()
            )
        )
        ->where('publicacion.resenas.0.puntuacion', 5)
        ->where('publicacion.resenas.0.comentario', 'Calidad impecable y muy buena atención.')
        ->where('publicacion.resenas.0.usuario.name', 'Carlos López')
        ->where('publicacion.resenas.1.puntuacion', 3)
        ->where('publicacion.resenas.1.comentario', 'Llegaron bien aunque con un poco de demora.')
        ->where('publicacion.resenas.1.usuario.name', 'Ana Martínez')
    );
});
