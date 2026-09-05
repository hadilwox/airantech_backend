<?php

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    Route::middleware(['api', 'auth:sanctum', 'permission:students.view'])
        ->get('/api/_test/students-only', fn () => response()->json(['ok' => true]));
});

it('denies access to a user without the required permission', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleName::Student->value);

    $this->actingAs($user)->getJson('/api/_test/students-only')->assertForbidden();
});

it('allows access to a user with the required permission', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);

    $this->actingAs($user)->getJson('/api/_test/students-only')->assertOk();
});

it('denies an unauthenticated guest with a 401 before the permission check', function () {
    $this->getJson('/api/_test/students-only')->assertUnauthorized();
});
