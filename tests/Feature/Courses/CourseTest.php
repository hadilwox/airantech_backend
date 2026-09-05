<?php

use App\Enums\CourseStatus;
use App\Enums\RoleName;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Instructor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleName::Admin->value);
});

it('denies access to a user without courses.view', function () {
    $studentUser = User::factory()->create();
    $studentUser->assignRole(RoleName::Student->value);

    $this->actingAs($studentUser)->getJson('/api/courses')->assertForbidden();
});

it('lists courses with category and instructor eager loaded', function () {
    Course::factory()->count(2)->create();

    $response = $this->actingAs($this->admin)->getJson('/api/courses');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('data.0.category.id'))->not->toBeNull();
});

it('filters courses by category, instructor, and status', function () {
    $category = CourseCategory::factory()->create();
    $instructor = Instructor::factory()->create();
    Course::factory()->create(['category_id' => $category->id, 'instructor_id' => $instructor->id, 'status' => CourseStatus::Ongoing]);
    Course::factory()->create(['status' => CourseStatus::Completed]);

    $response = $this->actingAs($this->admin)->getJson("/api/courses?category_id={$category->id}");
    expect($response->json('data'))->toHaveCount(1);

    $response = $this->actingAs($this->admin)->getJson("/api/courses?instructor_id={$instructor->id}");
    expect($response->json('data'))->toHaveCount(1);

    $response = $this->actingAs($this->admin)->getJson('/api/courses?status=completed');
    expect($response->json('data'))->toHaveCount(1);
});

it('creates a course and generates a unique business code server-side', function () {
    $category = CourseCategory::factory()->create(['code_prefix' => '3']);
    $instructor = Instructor::factory()->create();

    $response = $this->actingAs($this->admin)->postJson('/api/courses', [
        'category_id' => $category->id,
        'instructor_id' => $instructor->id,
        'title' => 'AI Fundamentals',
        'capacity' => 20,
        'tuition_fee' => 5000000,
        // A client-submitted code must be ignored — the server always
        // generates it via CourseCodeGenerator.
        'code' => 'HACKED-CODE',
    ]);

    $response->assertCreated();
    expect($response->json('code'))->toStartWith('3');
    expect($response->json('code'))->not->toBe('HACKED-CODE');
    $this->assertDatabaseHas('courses', ['title' => 'AI Fundamentals', 'category_id' => $category->id]);
});

it('increments the course code sequence for a second course in the same category', function () {
    $category = CourseCategory::factory()->create(['code_prefix' => '6']);

    $first = $this->actingAs($this->admin)->postJson('/api/courses', [
        'category_id' => $category->id, 'title' => 'Course A', 'capacity' => 10, 'tuition_fee' => 1000000,
    ]);
    $second = $this->actingAs($this->admin)->postJson('/api/courses', [
        'category_id' => $category->id, 'title' => 'Course B', 'capacity' => 10, 'tuition_fee' => 1000000,
    ]);

    expect($first->json('code'))->toEndWith('01');
    expect($second->json('code'))->toEndWith('02');
});

it('rejects an invalid category', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/courses', [
        'category_id' => 999999, 'title' => 'X', 'capacity' => 10, 'tuition_fee' => 100,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('category_id');
});

it('does not allow changing category_id on update', function () {
    $categoryA = CourseCategory::factory()->create();
    $categoryB = CourseCategory::factory()->create();
    $course = Course::factory()->create(['category_id' => $categoryA->id]);

    $response = $this->actingAs($this->admin)->putJson("/api/courses/{$course->id}", [
        'category_id' => $categoryB->id,
        'title' => 'Renamed',
    ]);

    $response->assertOk();
    expect($course->fresh()->category_id)->toBe($categoryA->id);
    expect($response->json('title'))->toBe('Renamed');
});

it('soft deletes a course', function () {
    $course = Course::factory()->create();

    $response = $this->actingAs($this->admin)->deleteJson("/api/courses/{$course->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('courses', ['id' => $course->id]);
});
