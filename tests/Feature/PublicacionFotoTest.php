<?php

use App\Models\Categoria;
use App\Models\Publicacion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('un productor puede crear una publicacion con fotos', function () {
    Storage::fake('public');

    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $foto1 = UploadedFile::fake()->image('tomate1.jpg');
    $foto2 = UploadedFile::fake()->image('tomate2.png');

    $response = $this
        ->actingAs($productor)
        ->post(route('publicaciones.store'), [
            'categoria_id' => $categoria->id,
            'titulo' => 'Tomates Frescos de Laguna Naineck',
            'descripcion' => 'Cosechados esta mañana.',
            'precio' => 1500,
            'unidad' => 'kg',
            'stock' => 100,
            'fotos' => [$foto1, $foto2],
        ]);

    $response->assertSessionHasNoErrors();

    $publicacion = Publicacion::first();
    $this->assertNotNull($publicacion);
    $response->assertRedirect(route('publicaciones.show', $publicacion));

    $this->assertCount(2, $publicacion->fotos);

    foreach ($publicacion->fotos as $foto) {
        Storage::disk('public')->assertExists($foto->ruta);
        $this->assertNotEmpty($foto->url);
    }
});

test('se rechaza una publicacion con mas de 6 fotos', function () {
    Storage::fake('public');

    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $fotos = [];
    for ($i = 0; $i < 7; $i++) {
        $fotos[] = UploadedFile::fake()->image("foto_{$i}.jpg");
    }

    $response = $this
        ->actingAs($productor)
        ->post(route('publicaciones.store'), [
            'categoria_id' => $categoria->id,
            'titulo' => 'Muchas fotos',
            'descripcion' => 'Intento superar el limite',
            'precio' => 1000,
            'unidad' => 'kg',
            'stock' => 50,
            'fotos' => $fotos,
        ]);

    $response->assertSessionHasErrors(['fotos']);
    $this->assertDatabaseEmpty('publicaciones');
});

test('se rechaza un archivo que no es una imagen valida o que pesa mas de 2 mb', function () {
    Storage::fake('public');

    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $archivoPdf = UploadedFile::fake()->create('documento.pdf', 500, 'application/pdf');
    $imagenPesada = UploadedFile::fake()->image('pesada.jpg')->size(3000); // 3 MB

    $response = $this
        ->actingAs($productor)
        ->post(route('publicaciones.store'), [
            'categoria_id' => $categoria->id,
            'titulo' => 'Archivos invalidos',
            'descripcion' => 'Probando validaciones',
            'precio' => 1000,
            'unidad' => 'kg',
            'stock' => 50,
            'fotos' => [$archivoPdf, $imagenPesada],
        ]);

    $response->assertSessionHasErrors(['fotos.0', 'fotos.1']);
    $this->assertDatabaseEmpty('publicaciones');
});

test('al editar una publicacion se pueden agregar y quitar fotos', function () {
    Storage::fake('public');

    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $productor->id,
        'categoria_id' => $categoria->id,
    ]);

    $fotoInicial = UploadedFile::fake()->image('inicial.jpg');
    $rutaInicial = $fotoInicial->store("publicaciones/{$publicacion->id}", 'public');

    $fotoModelo = $publicacion->fotos()->create([
        'ruta' => $rutaInicial,
        'orden' => 0,
    ]);

    Storage::disk('public')->assertExists($rutaInicial);

    $fotoNueva = UploadedFile::fake()->image('nueva.jpg');

    $response = $this
        ->actingAs($productor)
        ->put(route('publicaciones.update', $publicacion), [
            'categoria_id' => $categoria->id,
            'titulo' => 'Publicacion Editada con Nueva Foto',
            'descripcion' => $publicacion->descripcion,
            'precio' => $publicacion->precio,
            'unidad' => $publicacion->unidad,
            'stock' => $publicacion->stock,
            'fotos' => [$fotoNueva],
            'fotos_eliminar' => [$fotoModelo->id],
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('publicaciones.show', $publicacion));

    // La foto inicial se borró de la base y del disco
    $this->assertDatabaseMissing('publicacion_fotos', ['id' => $fotoModelo->id]);
    Storage::disk('public')->assertMissing($rutaInicial);

    // Quedó la foto nueva
    $publicacion->refresh();
    $this->assertCount(1, $publicacion->fotos);
    Storage::disk('public')->assertExists($publicacion->fotos->first()->ruta);
});

test('otro usuario no puede modificar ni borrar fotos de una publicacion ajena', function () {
    Storage::fake('public');

    $dueno = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $otroProductor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $categoria = Categoria::factory()->create();

    $publicacion = Publicacion::factory()->create([
        'usuario_id' => $dueno->id,
        'categoria_id' => $categoria->id,
    ]);

    $fotoInicial = UploadedFile::fake()->image('dueno.jpg');
    $rutaInicial = $fotoInicial->store("publicaciones/{$publicacion->id}", 'public');
    $foto = $publicacion->fotos()->create([
        'ruta' => $rutaInicial,
        'orden' => 0,
    ]);

    $response = $this
        ->actingAs($otroProductor)
        ->put(route('publicaciones.update', $publicacion), [
            'fotos_eliminar' => [$foto->id],
        ]);

    $response->assertForbidden();

    // La foto debe seguir existiendo intacta
    $this->assertDatabaseHas('publicacion_fotos', ['id' => $foto->id]);
    Storage::disk('public')->assertExists($rutaInicial);
});

test('al eliminar una publicacion se borran sus fotos asociadas del disco y base de datos', function () {
    Storage::fake('public');

    $productor = User::factory()->create(['rol' => User::ROL_PRODUCTOR]);
    $publicacion = Publicacion::factory()->create(['usuario_id' => $productor->id]);

    $archivo1 = UploadedFile::fake()->image('p1.jpg');
    $archivo2 = UploadedFile::fake()->image('p2.jpg');

    $ruta1 = $archivo1->store("publicaciones/{$publicacion->id}", 'public');
    $ruta2 = $archivo2->store("publicaciones/{$publicacion->id}", 'public');

    $foto1 = $publicacion->fotos()->create(['ruta' => $ruta1, 'orden' => 0]);
    $foto2 = $publicacion->fotos()->create(['ruta' => $ruta2, 'orden' => 1]);

    Storage::disk('public')->assertExists($ruta1);
    Storage::disk('public')->assertExists($ruta2);

    // Al borrar la publicación se disparan los eventos
    $publicacion->fotos->each->delete();
    $publicacion->delete();

    $this->assertDatabaseMissing('publicacion_fotos', ['id' => $foto1->id]);
    $this->assertDatabaseMissing('publicacion_fotos', ['id' => $foto2->id]);
    $this->assertDatabaseMissing('publicaciones', ['id' => $publicacion->id]);

    Storage::disk('public')->assertMissing($ruta1);
    Storage::disk('public')->assertMissing($ruta2);
});
