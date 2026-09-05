<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs the authenticated user out and invalidates the session', function () {
    $user = User::factory()->create(['password' => 'Password123!']);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'Password123!',
    ])->assertOk();

    $this->assertAuthenticatedAs($user, 'web');

    $this->postJson('/api/logout')->assertOk();

    $this->assertGuest('web');
});

it('rejects logout for a guest', function () {
    $this->postJson('/api/logout')->assertUnauthorized();
});
