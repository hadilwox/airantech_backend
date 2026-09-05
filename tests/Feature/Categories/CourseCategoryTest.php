<?php

use App\Enums\RoleName;
use App\Models\CourseCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleName::Admin->value);
});

it('denies access to a user without categories.view', function () {
    $student = User::factory()->create();
    $student->assignRole(RoleName::Student->value);

    $this->actingAs($student)->getJson('/api/course-categories')->assertForbidden();
});

it('lists categories for an authorized user', function () {
    CourseCategory::factory()->count(3)->create();

    $response = $this->actingAs($this->admin)->getJson('/api/course-categories');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('searches categories by name', function () {
    CourseCategory::factory()->create(['name' => 'Artificial Intelligence']);
    CourseCategory::factory()->create(['name' => 'Web Development']);

    $response = $this->actingAs($this->admin)->getJson('/api/course-categories?q=Artificial');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('creates a category with valid data', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/course-categories', [
        'name' => 'Programming',
        'code_prefix' => '3',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('name', 'Programming');
    $this->assertDatabaseHas('course_categories', ['name' => 'Programming', 'code_prefix' => '3']);
});

it('rejects a duplicate category name', function () {
    CourseCategory::factory()->create(['name' => 'Programming']);

    $response = $this->actingAs($this->admin)->postJson('/api/course-categories', [
        'name' => 'Programming',
        'code_prefix' => '9',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('name');
});

it('rejects a duplicate code_prefix', function () {
    CourseCategory::factory()->create(['code_prefix' => '3']);

    $response = $this->actingAs($this->admin)->postJson('/api/course-categories', [
        'name' => 'Something Else',
        'code_prefix' => '3',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('code_prefix');
});

it('updates a category', function () {
    $category = CourseCategory::factory()->create();

    $response = $this->actingAs($this->admin)->putJson("/api/course-categories/{$category->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertOk();
    $response->assertJsonPath('name', 'Updated Name');
});

it('allows keeping the same name/prefix on update', function () {
    $category = CourseCategory::factory()->create(['name' => 'Design', 'code_prefix' => '4']);

    $response = $this->actingAs($this->admin)->putJson("/api/course-categories/{$category->id}", [
        'name' => 'Design',
        'code_prefix' => '4',
        'is_active' => false,
    ]);

    $response->assertOk();
    $response->assertJsonPath('is_active', false);
});

it('deletes a category', function () {
    $category = CourseCategory::factory()->create();

    $response = $this->actingAs($this->admin)->deleteJson("/api/course-categories/{$category->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('course_categories', ['id' => $category->id]);
});
