<?php

use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('registers a new student, assigns the Student role, and starts a session', function () {
    $payload = [
        'email' => 'student@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'first_name' => 'سارا',
        'last_name' => 'احمدی',
        'national_id' => '1234567890',
        'mobile_phone' => '09121234567',
    ];

    $response = $this->postJson('/api/register/student', $payload);

    $response->assertCreated();
    $response->assertJsonPath('roles', ['Student']);

    $user = User::where('email', 'student@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('Student'))->toBeTrue();
    expect(Student::where('user_id', $user->id)->where('national_id', '1234567890')->exists())->toBeTrue();

    // Laravel's JsonResource reports 201 whenever the underlying model still
    // has wasRecentlyCreated=true (true here since the guard holds the very
    // same in-memory instance created moments ago) — assert success, not 200.
    $this->getJson('/api/me')->assertSuccessful()->assertJsonPath('email', 'student@example.com');
});

it('rejects registration with a duplicate email', function () {
    $existing = User::factory()->create(['email' => 'dupe@example.com']);
    Student::factory()->create(['user_id' => $existing->id, 'national_id' => '1111111111']);

    $response = $this->postJson('/api/register/student', [
        'email' => 'dupe@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'first_name' => 'test',
        'last_name' => 'test',
        'national_id' => '2222222222',
        'mobile_phone' => '09121234567',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('email');
});

it('rejects registration with a duplicate national id', function () {
    $existing = User::factory()->create();
    Student::factory()->create(['user_id' => $existing->id, 'national_id' => '3333333333']);

    $response = $this->postJson('/api/register/student', [
        'email' => 'unique@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'first_name' => 'test',
        'last_name' => 'test',
        'national_id' => '3333333333',
        'mobile_phone' => '09121234567',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('national_id');
});
