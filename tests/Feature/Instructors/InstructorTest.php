<?php

use App\Enums\InstructorStatus;
use App\Enums\RoleName;
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

it('denies access to a user without instructors.view', function () {
    $studentUser = User::factory()->create();
    $studentUser->assignRole(RoleName::Student->value);

    $this->actingAs($studentUser)->getJson('/api/instructors')->assertForbidden();
});

it('lists instructors', function () {
    Instructor::factory()->count(2)->create();

    $response = $this->actingAs($this->admin)->getJson('/api/instructors');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('filters instructors by status', function () {
    Instructor::factory()->create(['status' => InstructorStatus::Pending]);
    Instructor::factory()->create(['status' => InstructorStatus::Active]);

    $response = $this->actingAs($this->admin)->getJson('/api/instructors?status=pending');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('creates an instructor as Active by default (admin-vetted, unlike self-application)', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/instructors', [
        'email' => 'newinstructor@example.com',
        'first_name' => 'علی',
        'last_name' => 'رضایی',
        'national_id' => '5550001111',
        'mobile_phone' => '09121234567',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('status', InstructorStatus::Active->value);

    $instructor = Instructor::where('national_id', '5550001111')->first();
    expect($instructor->user->hasRole('Instructor'))->toBeTrue();
});

it('rejects a duplicate national id on create', function () {
    Instructor::factory()->create(['national_id' => '7778889999']);

    $response = $this->actingAs($this->admin)->postJson('/api/instructors', [
        'email' => 'dupe@example.com',
        'first_name' => 'test',
        'last_name' => 'test',
        'national_id' => '7778889999',
        'mobile_phone' => '09121234567',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('national_id');
});

it('updates an instructor profile field', function () {
    $instructor = Instructor::factory()->create();

    $response = $this->actingAs($this->admin)->putJson("/api/instructors/{$instructor->id}", [
        'skills' => 'Laravel, Vue',
    ]);

    $response->assertOk();
    $response->assertJsonPath('skills', 'Laravel, Vue');
});

it('requires instructors.approve to change status', function () {
    $manager = User::factory()->create();
    $manager->assignRole(RoleName::Instructor->value); // has instructors.update? no — only broad roles do

    $instructor = Instructor::factory()->create(['status' => InstructorStatus::Pending]);

    $response = $this->actingAs($manager)->putJson("/api/instructors/{$instructor->id}", [
        'status' => InstructorStatus::Approved->value,
    ]);

    $response->assertForbidden();
});

it('allows an admin to approve a pending instructor', function () {
    $instructor = Instructor::factory()->create(['status' => InstructorStatus::Pending]);

    $response = $this->actingAs($this->admin)->putJson("/api/instructors/{$instructor->id}", [
        'status' => InstructorStatus::Approved->value,
    ]);

    $response->assertOk();
    $response->assertJsonPath('status', InstructorStatus::Approved->value);
});

it('soft deletes an instructor', function () {
    $instructor = Instructor::factory()->create();

    $response = $this->actingAs($this->admin)->deleteJson("/api/instructors/{$instructor->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('instructors', ['id' => $instructor->id]);
});
