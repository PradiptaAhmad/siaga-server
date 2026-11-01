<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can log in and receives a session cookie', function () {
    $user = User::factory()->create([
        'email'    => 'user@example.com',
        'password' => 'password',
    ]);

    $response = $this->withoutMiddleware(ValidateCsrfToken::class)->postJson('/api/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertCookie(config('session.cookie'));
});

test('invalid credentials cannot log in', function () {
    User::factory()->create([
        'email'    => 'user@example.com',
        'password' => 'password',
    ]);

    $response = $this->withoutMiddleware(ValidateCsrfToken::class)->postJson('/api/login', [
        'email'    => 'user@example.com',
        'password' => 'incorrect-password',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('an authenticated user can view their profile and log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id);

    $this->withoutMiddleware(ValidateCsrfToken::class)
        ->actingAs($user)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJson(['message' => 'Successfully logged out.']);
});

test('an unauthenticated user cannot view their profile', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});
