<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('un administrador puede ver el listado de publicaciones para moderacion con filtros', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $categoria1 = Categoria::factory()->create(['nombre' => 'Verduras']);
    $categoria2 = Categoria::factory()->create(['nombre' => 'Frutas']);

    Publicacion::factory()->create([
        'titulo' => 'Tomate Platense',
        'descripcion' => 'Excelente verdura',
        'categoria_id' => $categoria1->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    Publicacion::factory()->create([
        'titulo' => 'Banana Naineck',
        'descripcion' => 'Fruta de calidad',
        'categoria_id' => $categoria2->id,
        'estado' => Publicacion::ESTADO_PAUSADA,
    ]);

    // Ver todas
    $response = $this->actingAs($admin)->get(route('admin.publicaciones.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Publicaciones/Index')
        ->has('publicaciones.data', 2)
    );

    // Filtrar por término de búsqueda ?q=Tomate
    $response = $this->actingAs($admin)->get(route('admin.publicaciones.index', ['q' => 'Tomate']));
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Publicaciones/Index')
        ->has('publicaciones.data', 1)
        ->where('publicaciones.data.0.titulo', 'Tomate Platense')
    );

    // Filtrar por estado ?estado=pausada
    $response = $this->actingAs($admin)->get(route('admin.publicaciones.index', ['estado' => Publicacion::ESTADO_PAUSADA]));
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Publicaciones/Index')
        ->has('publicaciones.data', 1)
        ->where('publicaciones.data.0.titulo', 'Banana Naineck')
    );
});

test('un administrador puede bloquear una publicacion indicando un motivo', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('admin.publicaciones.bloquear', $publicacion), [
            'motivo' => 'Publicación con contenido inapropiado o precio engañoso.',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('success');

    $publicacion->refresh();
    expect($publicacion->estado)->toBe(Publicacion::ESTADO_BLOQUEADA);
    expect($publicacion->motivo_bloqueo)->toBe('Publicación con contenido inapropiado o precio engañoso.');
    expect($publicacion->bloqueada_por)->toBe($admin->id);
    expect($publicacion->bloqueada_at)->not->toBeNull();
});

test('bloquear una publicacion requiere un motivo obligatorio y maximo 1000 caracteres', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $publicacion = Publicacion::factory()->create(['estado' => Publicacion::ESTADO_ACTIVA]);

    // Sin motivo
    $response = $this->actingAs($admin)
        ->patch(route('admin.publicaciones.bloquear', $publicacion), []);

    $response->assertSessionHasErrors(['motivo']);

    // Motivo excesivamente largo
    $response = $this->actingAs($admin)
        ->patch(route('admin.publicaciones.bloquear', $publicacion), [
            'motivo' => str_repeat('a', 1001),
        ]);

    $response->assertSessionHasErrors(['motivo']);
});

test('una publicacion bloqueada no aparece en el listado publico', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Publicacion Bloqueada',
        'estado' => Publicacion::ESTADO_BLOQUEADA,
    ]);

    Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'titulo' => 'Publicacion Activa',
        'estado' => Publicacion::ESTADO_ACTIVA,
    ]);

    $response = $this->actingAs($productor)->get(route('publicaciones.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Publicaciones/Index')
        ->has('publicaciones.data', 1)
        ->where('publicaciones.data.0.titulo', 'Publicacion Activa')
    );
});

test('el dueno no puede cambiar el estado ni editar una publicacion bloqueada', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_BLOQUEADA,
    ]);

    // Intento de cambiar estado
    $response = $this->actingAs($productor)
        ->patch(route('publicaciones.estado', $publicacion), [
            'estado' => Publicacion::ESTADO_ACTIVA,
        ]);

    $response->assertForbidden();

    // Intento de actualizar datos
    $response = $this->actingAs($productor)
        ->put(route('publicaciones.update', $publicacion), [
            'titulo' => 'Intento burlar el bloqueo',
        ]);

    $response->assertForbidden();
});

test('el observer no reactiva automaticamente por stock una publicacion bloqueada', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'estado' => Publicacion::ESTADO_BLOQUEADA,
        'stock' => 0,
    ]);

    // Se repone stock
    $publicacion->stock = 50;
    $publicacion->save();

    $publicacion->refresh();
    expect($publicacion->estado)->toBe(Publicacion::ESTADO_BLOQUEADA);
});

test('un administrador puede desbloquear una publicacion y queda en estado pausada', function () {
    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $publicacion = Publicacion::factory()->create([
        'estado' => Publicacion::ESTADO_BLOQUEADA,
        'motivo_bloqueo' => 'Faltaba habilitación',
        'bloqueada_por' => $admin->id,
        'bloqueada_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->patch(route('admin.publicaciones.desbloquear', $publicacion));

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('success');

    $publicacion->refresh();
    expect($publicacion->estado)->toBe(Publicacion::ESTADO_PAUSADA);
    expect($publicacion->motivo_bloqueo)->toBeNull();
    expect($publicacion->bloqueada_por)->toBeNull();
    expect($publicacion->bloqueada_at)->toBeNull();
});

test('un administrador puede eliminar definitivamente una publicacion y sus fotos asociadas', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
    $publicacion = Publicacion::factory()->create();

    $foto = UploadedFile::fake()->image('foto_admin.jpg');
    $ruta = $foto->store("publicaciones/{$publicacion->id}", 'public');
    $publicacionFoto = $publicacion->fotos()->create(['ruta' => $ruta, 'orden' => 0]);

    Storage::disk('public')->assertExists($ruta);

    $response = $this->actingAs($admin)
        ->delete(route('admin.publicaciones.destroy', $publicacion));

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('publicaciones', ['id' => $publicacion->id]);
    $this->assertDatabaseMissing('publicacion_fotos', ['id' => $publicacionFoto->id]);
    Storage::disk('public')->assertMissing($ruta);
});

test('usuarios no administradores reciben 403 en todas las rutas de moderacion de publicaciones', function () {
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $comprador = User::factory()->create(['rol' => User::ROL_COMPRADOR]);
    $publicacion = Publicacion::factory()->create();

    // Productor
    $this->actingAs($productor)->get(route('admin.publicaciones.index'))->assertForbidden();
    $this->actingAs($productor)->patch(route('admin.publicaciones.bloquear', $publicacion))->assertForbidden();
    $this->actingAs($productor)->patch(route('admin.publicaciones.desbloquear', $publicacion))->assertForbidden();
    $this->actingAs($productor)->delete(route('admin.publicaciones.destroy', $publicacion))->assertForbidden();

    // Comprador
    $this->actingAs($comprador)->get(route('admin.publicaciones.index'))->assertForbidden();
    $this->actingAs($comprador)->patch(route('admin.publicaciones.bloquear', $publicacion))->assertForbidden();
    $this->actingAs($comprador)->patch(route('admin.publicaciones.desbloquear', $publicacion))->assertForbidden();
    $this->actingAs($comprador)->delete(route('admin.publicaciones.destroy', $publicacion))->assertForbidden();
});
