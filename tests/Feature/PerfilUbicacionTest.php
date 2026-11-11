<?php

use App\Models\User;

test('un usuario autenticado puede guardar lat y lng válidos en su perfil', function () {
    $user = User::factory()->create([
        'lat' => null,
        'lng' => null,
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Usuario con Ubicación',
        'email' => $user->email,
        'telefono' => $user->telefono,
        'lat' => -25.2458123,
        'lng' => -57.7345678,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect((float) $user->lat)->toBe(-25.2458123);
    expect((float) $user->lng)->toBe(-57.7345678);
});

test('latitud o longitud fuera de rango son rechazadas por la validación', function () {
    $user = User::factory()->create();

    // Latitud mayor a 90
    $responseLatMayor = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'lat' => 95.123456,
        'lng' => -57.123456,
    ]);
    $responseLatMayor->assertSessionHasErrors(['lat']);

    // Latitud menor a -90
    $responseLatMenor = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'lat' => -95.123456,
        'lng' => -57.123456,
    ]);
    $responseLatMenor->assertSessionHasErrors(['lat']);

    // Longitud mayor a 180
    $responseLngMayor = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'lat' => -25.123456,
        'lng' => 185.123456,
    ]);
    $responseLngMayor->assertSessionHasErrors(['lng']);

    // Longitud menor a -180
    $responseLngMenor = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'lat' => -25.123456,
        'lng' => -185.123456,
    ]);
    $responseLngMenor->assertSessionHasErrors(['lng']);

    // Valores no numéricos
    $responseNoNumerico = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'lat' => 'coordenada-falsa',
        'lng' => 'coordenada-falsa',
    ]);
    $responseNoNumerico->assertSessionHasErrors(['lat', 'lng']);
});

test('no enviar lat y lng no rompe la actualización del perfil y mantiene o setea null', function () {
    $user = User::factory()->create([
        'lat' => null,
        'lng' => null,
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Nombre Actualizado Sin Coordenadas',
        'email' => 'nuevo-email@ejemplo.com',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->name)->toBe('Nombre Actualizado Sin Coordenadas');
    expect($user->email)->toBe('nuevo-email@ejemplo.com');
    expect($user->lat)->toBeNull();
    expect($user->lng)->toBeNull();
});

test('un usuario puede vaciar sus coordenadas enviando null', function () {
    $user = User::factory()->create([
        'lat' => -25.2458123,
        'lng' => -57.7345678,
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'lat' => null,
        'lng' => null,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->lat)->toBeNull();
    expect($user->lng)->toBeNull();
});
