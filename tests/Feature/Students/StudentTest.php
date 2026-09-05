<?php

use App\Enums\RoleName;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RoleName::Admin->value);
});

it('denies access to a user without students.view', function () {
    $studentUser = User::factory()->create();
    $studentUser->assignRole(RoleName::Student->value);

    $this->actingAs($studentUser)->getJson('/api/students')->assertForbidden();
});

it('lists students', function () {
    Student::factory()->count(3)->create();

    $response = $this->actingAs($this->admin)->getJson('/api/students');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
});

it('searches students by name and national id', function () {
    Student::factory()->create(['first_name' => 'Zahra', 'national_id' => '1111111111']);
    Student::factory()->create(['first_name' => 'Sara', 'national_id' => '2222222222']);

    $response = $this->actingAs($this->admin)->getJson('/api/students?q=Zahra');
    expect($response->json('data'))->toHaveCount(1);

    $response = $this->actingAs($this->admin)->getJson('/api/students?q=2222222222');
    expect($response->json('data'))->toHaveCount(1);
});

it('creates a student along with its user account and a generated student code', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/students', [
        'email' => 'newstudent@example.com',
        'first_name' => 'سارا',
        'last_name' => 'احمدی',
        'national_id' => '1234500000',
        'mobile_phone' => '09121234567',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('email', 'newstudent@example.com');
    expect($response->json('student_code'))->toStartWith('STU-');

    $this->assertDatabaseHas('users', ['email' => 'newstudent@example.com']);
    $student = Student::where('national_id', '1234500000')->first();
    expect($student->user->hasRole('Student'))->toBeTrue();
});

it('rejects a duplicate national id on create', function () {
    Student::factory()->create(['national_id' => '9999999999']);

    $response = $this->actingAs($this->admin)->postJson('/api/students', [
        'email' => 'another@example.com',
        'first_name' => 'test',
        'last_name' => 'test',
        'national_id' => '9999999999',
        'mobile_phone' => '09121234567',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('national_id');
});

it('shows a student', function () {
    $student = Student::factory()->create();

    $response = $this->actingAs($this->admin)->getJson("/api/students/{$student->id}");

    $response->assertOk();
    $response->assertJsonPath('id', $student->id);
});

it('updates a student profile field', function () {
    $student = Student::factory()->create();

    $response = $this->actingAs($this->admin)->putJson("/api/students/{$student->id}", [
        'first_name' => 'Updated',
    ]);

    $response->assertOk();
    $response->assertJsonPath('first_name', 'Updated');
});

it('deactivates a student by updating the linked user', function () {
    $student = Student::factory()->create();

    $response = $this->actingAs($this->admin)->putJson("/api/students/{$student->id}", [
        'is_active' => false,
    ]);

    $response->assertOk();
    $response->assertJsonPath('is_active', false);
    expect($student->user->fresh()->is_active)->toBeFalse();
});

it('soft deletes a student', function () {
    $student = Student::factory()->create();

    $response = $this->actingAs($this->admin)->deleteJson("/api/students/{$student->id}");

    $response->assertNoContent();
    $this->assertSoftDeleted('students', ['id' => $student->id]);
});
