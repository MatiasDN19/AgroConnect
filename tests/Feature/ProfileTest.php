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
            'telefono' => '+54 9 3718 123456',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertSame('+54 9 3718 123456', $user->telefono);
    $this->assertNull($user->email_verified_at);
});

test('profile phone number is optional', function () {
    $user = User::factory()->create([
        'telefono' => '+54 9 3718 123456',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => null,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNull($user->refresh()->telefono);
});

test('profile phone number can be omitted from request', function () {
    $user = User::factory()->create([
        'telefono' => '+54 9 3718 123456',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertSame('Updated Name', $user->refresh()->name);
});

test('updating phone number does not change email verification status', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => '+54 9 3718 654321',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();
    $this->assertSame('+54 9 3718 654321', $user->telefono);
    $this->assertNotNull($user->email_verified_at);
});

test('profile phone number cannot exceed 50 characters', function () {
    $user = User::factory()->create([
        'telefono' => '12345',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => str_repeat('1', 51),
        ]);

    $response->assertSessionHasErrors('telefono');
    $this->assertSame('12345', $user->refresh()->telefono);
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
