<?php

use App\Enums\InstructorStatus;
use App\Models\Instructor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('registers a new instructor application as pending and assigns the Instructor role', function () {
    $payload = [
        'email' => 'instructor@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'first_name' => 'علی',
        'last_name' => 'رضایی',
        'national_id' => '9876543210',
        'mobile_phone' => '09121234567',
    ];

    $response = $this->postJson('/api/register/instructor', $payload);

    $response->assertCreated();
    $response->assertJsonPath('roles', ['Instructor']);
    $response->assertJsonPath('instructor.status', InstructorStatus::Pending->value);

    $user = User::where('email', 'instructor@example.com')->first();
    expect($user->hasRole('Instructor'))->toBeTrue();

    $instructor = Instructor::where('user_id', $user->id)->first();
    expect($instructor->status)->toBe(InstructorStatus::Pending);
});
