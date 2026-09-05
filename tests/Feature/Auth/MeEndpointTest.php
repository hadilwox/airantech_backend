<?php

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('rejects an unauthenticated request', function () {
    $this->getJson('/api/me')->assertUnauthorized();
});

it('returns the full permission set for Admin', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);

    $response = $this->actingAs($user)->getJson('/api/me');

    $response->assertOk();
    $response->assertJsonPath('roles', ['Admin']);
    expect($response->json('permissions'))->toContain('students.view', 'roles.manage', 'settings.manage');
});

it('returns no blanket permissions for Student', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleName::Student->value);

    $response = $this->actingAs($user)->getJson('/api/me');

    $response->assertOk();
    $response->assertJsonPath('roles', ['Student']);
    expect($response->json('permissions'))->toBe([]);
});

it('returns the operational permission set for Academy Manager, excluding roles.manage', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleName::AcademyManager->value);

    $response = $this->actingAs($user)->getJson('/api/me');

    $response->assertOk();
    $permissions = $response->json('permissions');
    expect($permissions)->toContain('students.view');
    expect($permissions)->not->toContain('roles.manage');
});
