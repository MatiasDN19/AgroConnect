<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can update phone number in profile without resetting email verification', function () {
    $user = User::factory()->create([
        'telefono' => null,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => '+54 9 3718 123456',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('+54 9 3718 123456', $user->telefono);
    $this->assertNotNull($user->email_verified_at);
});

test('phone number is optional and can be cleared when updating profile', function () {
    $user = User::factory()->create([
        'telefono' => '+54 9 3718 123456',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test UserSinTelefono',
            'email' => $user->email,
            'telefono' => null,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test UserSinTelefono', $user->name);
    $this->assertNull($user->telefono);
});

test('phone number cannot exceed 50 characters', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => str_repeat('1', 51),
        ]);

    $response
        ->assertSessionHasErrors('telefono')
        ->assertRedirect('/profile');
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
