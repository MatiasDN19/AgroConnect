<?php

use App\Models\Categoria;
use App\Models\Favorito;
use App\Models\Publicacion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('los invitados no autenticados son redirigidos al login al intentar interactuar con favoritos', function () {
    $publicacion = Publicacion::factory()->create();

    $this->get(route('favoritos.index'))->assertRedirect(route('login'));
    $this->post(route('favoritos.store', $publicacion))->assertRedirect(route('login'));
    $this->delete(route('favoritos.destroy', $publicacion))->assertRedirect(route('login'));
});

test('un usuario autenticado puede agregar una publicación a sus favoritos', function () {
    $usuario = User::factory()->create();
    $publicacion = Publicacion::factory()->create(['titulo' => 'Miel pura de monte']);

    $response = $this->actingAs($usuario)
        ->from(route('publicaciones.show', $publicacion))
        ->post(route('favoritos.store', $publicacion));

    $response->assertRedirect(route('publicaciones.show', $publicacion));
    $response->assertSessionHas('success', 'Publicación agregada a tus favoritos.');

    $this->assertDatabaseHas('favoritos', [
        'usuario_id' => $usuario->id,
        'publicacion_id' => $publicacion->id,
    ]);
});

test('agregar una publicación a favoritos es idempotente y no duplica registros', function () {
    $usuario = User::factory()->create();
    $publicacion = Publicacion::factory()->create();

    // Primer intento
    $this->actingAs($usuario)->post(route('favoritos.store', $publicacion));

    // Segundo intento con la misma publicación y usuario
    $response = $this->actingAs($usuario)->post(route('favoritos.store', $publicacion));

    $response->assertSessionHasNoErrors();

    // Debe existir exactamente 1 registro en la tabla
    expect(Favorito::where('usuario_id', $usuario->id)->where('publicacion_id', $publicacion->id)->count())->toBe(1);
});

test('el listado de favoritos muestra únicamente las publicaciones agregadas por el usuario autenticado', function () {
    $usuario = User::factory()->create();
    $otroUsuario = User::factory()->create();

    $categoria = Categoria::factory()->create(['nombre' => 'Frutas']);
    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR, 'name' => 'Don Juan']);

    $publicacion1 = Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'usuario_id' => $productor->id,
        'titulo' => 'Bananas',
    ]);

    $publicacion2 = Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'usuario_id' => $productor->id,
        'titulo' => 'Mandarinas',
    ]);

    $publicacionAjena = Publicacion::factory()->create([
        'categoria_id' => $categoria->id,
        'usuario_id' => $productor->id,
        'titulo' => 'Naranjas de otro',
    ]);

    // Favoritos de usuario con timestamps diferenciados
    Favorito::create([
        'usuario_id' => $usuario->id,
        'publicacion_id' => $publicacion1->id,
        'created_at' => now()->subMinutes(10),
    ]);
    Favorito::create([
        'usuario_id' => $usuario->id,
        'publicacion_id' => $publicacion2->id,
        'created_at' => now(),
    ]);

    // Favorito de otro usuario
    Favorito::create(['usuario_id' => $otroUsuario->id, 'publicacion_id' => $publicacionAjena->id]);

    $response = $this->actingAs($usuario)->get(route('favoritos.index'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Favoritos/Index')
        ->has('favoritos', 2)
        ->where('favoritos.0.publicacion.titulo', 'Mandarinas')
        ->where('favoritos.1.publicacion.titulo', 'Bananas')
    );
});

test('un usuario puede quitar su propio favorito', function () {
    $usuario = User::factory()->create();
    $publicacion = Publicacion::factory()->create();

    Favorito::create([
        'usuario_id' => $usuario->id,
        'publicacion_id' => $publicacion->id,
    ]);

    $response = $this->actingAs($usuario)
        ->from(route('publicaciones.show', $publicacion))
        ->delete(route('favoritos.destroy', $publicacion));

    $response->assertRedirect(route('publicaciones.show', $publicacion));
    $response->assertSessionHas('success', 'Publicación eliminada de tus favoritos.');

    $this->assertDatabaseMissing('favoritos', [
        'usuario_id' => $usuario->id,
        'publicacion_id' => $publicacion->id,
    ]);
});

test('un usuario no puede eliminar el favorito de otro usuario', function () {
    $duenoFavorito = User::factory()->create();
    $otroUsuario = User::factory()->create();
    $publicacion = Publicacion::factory()->create();

    Favorito::create([
        'usuario_id' => $duenoFavorito->id,
        'publicacion_id' => $publicacion->id,
    ]);

    // Otro usuario intenta eliminar el favorito
    $response = $this->actingAs($otroUsuario)->delete(route('favoritos.destroy', $publicacion));

    $response->assertSessionHasNoErrors();

    // El favorito del dueño original sigue existiendo intacto
    $this->assertDatabaseHas('favoritos', [
        'usuario_id' => $duenoFavorito->id,
        'publicacion_id' => $publicacion->id,
    ]);
});
