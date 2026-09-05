<?php

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('logs a user in with correct credentials', function () {
    $user = User::factory()->create(['password' => 'Password123!']);
    $user->assignRole(RoleName::Student->value);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'Password123!',
    ]);

    $response->assertOk();
    $response->assertJsonPath('email', $user->email);
    $this->assertAuthenticatedAs($user, 'web');
});

it('rejects an incorrect password', function () {
    $user = User::factory()->create(['password' => 'Password123!']);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('email');
    $this->assertGuest('web');
});

it('blocks login for an inactive user', function () {
    $user = User::factory()->create(['password' => 'Password123!', 'is_active' => false]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'Password123!',
    ]);

    $response->assertUnprocessable();
    $this->assertGuest('web');
});

it('rate limits repeated login attempts', function () {
    $user = User::factory()->create(['password' => 'Password123!']);

    for ($i = 0; $i < 6; $i++) {
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong']);
    }

    $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong']);

    $response->assertStatus(429);
});
