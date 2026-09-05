<?php

use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\RoleName;
use App\Models\Course;
use App\Models\Enrollment;
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

it('denies access to a user without enrollments.create', function () {
    $studentUser = User::factory()->create();
    $studentUser->assignRole(RoleName::Student->value);
    $student = Student::factory()->create();
    $course = Course::factory()->create();

    $this->actingAs($studentUser)->postJson('/api/enrollments', [
        'student_id' => $student->id,
        'course_id' => $course->id,
    ])->assertForbidden();
});

it('enrolls a student and sets tuition from the course, ignoring any client-submitted amount', function () {
    $student = Student::factory()->create();
    $course = Course::factory()->create(['tuition_fee' => 4_500_000, 'capacity' => 5]);

    $response = $this->actingAs($this->admin)->postJson('/api/enrollments', [
        'student_id' => $student->id,
        'course_id' => $course->id,
        // Attempting to trust the client — must be ignored server-side.
        'tuition_amount' => 1,
        'payment_status' => 'fully_paid',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('tuition_amount', '4500000.00');
    $response->assertJsonPath('payment_status', EnrollmentPaymentStatus::Unpaid->value);
    $response->assertJsonPath('status', EnrollmentStatus::Active->value);
});

it('prevents enrolling the same student in the same course twice', function () {
    $student = Student::factory()->create();
    $course = Course::factory()->create(['capacity' => 5]);
    Enrollment::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

    $response = $this->actingAs($this->admin)->postJson('/api/enrollments', [
        'student_id' => $student->id,
        'course_id' => $course->id,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('student_id');
});

it('rejects enrollment once the course capacity is reached', function () {
    $course = Course::factory()->create(['capacity' => 1]);
    Enrollment::factory()->create(['course_id' => $course->id, 'status' => EnrollmentStatus::Active]);

    $newStudent = Student::factory()->create();

    $response = $this->actingAs($this->admin)->postJson('/api/enrollments', [
        'student_id' => $newStudent->id,
        'course_id' => $course->id,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('course_id');
});

it('does not count cancelled/withdrawn enrollments against capacity', function () {
    $course = Course::factory()->create(['capacity' => 1]);
    Enrollment::factory()->create(['course_id' => $course->id, 'status' => EnrollmentStatus::Withdrawn]);

    $newStudent = Student::factory()->create();

    $response = $this->actingAs($this->admin)->postJson('/api/enrollments', [
        'student_id' => $newStudent->id,
        'course_id' => $course->id,
    ]);

    $response->assertCreated();
});

it('derives payment_status automatically as paid_amount changes', function () {
    $course = Course::factory()->create(['tuition_fee' => 1_000_000]);
    $enrollment = Enrollment::factory()->create(['course_id' => $course->id, 'tuition_amount' => 1_000_000, 'paid_amount' => 0]);

    $partial = $this->actingAs($this->admin)->putJson("/api/enrollments/{$enrollment->id}", ['paid_amount' => 400000]);
    $partial->assertOk();
    $partial->assertJsonPath('payment_status', EnrollmentPaymentStatus::PartiallyPaid->value);

    $full = $this->actingAs($this->admin)->putJson("/api/enrollments/{$enrollment->id}", ['paid_amount' => 1000000]);
    $full->assertOk();
    $full->assertJsonPath('payment_status', EnrollmentPaymentStatus::FullyPaid->value);
});

it('rejects paying more than the tuition amount', function () {
    $enrollment = Enrollment::factory()->create(['tuition_amount' => 500000, 'paid_amount' => 0]);

    $response = $this->actingAs($this->admin)->putJson("/api/enrollments/{$enrollment->id}", ['paid_amount' => 600000]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('paid_amount');
});

it('lists a course’s enrolled students via the nested route', function () {
    $course = Course::factory()->create();
    Enrollment::factory()->count(2)->create(['course_id' => $course->id]);
    Enrollment::factory()->create(); // unrelated course

    $response = $this->actingAs($this->admin)->getJson("/api/courses/{$course->id}/students");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('lists a student’s courses via the nested route', function () {
    $student = Student::factory()->create();
    Enrollment::factory()->count(2)->create(['student_id' => $student->id]);
    Enrollment::factory()->create(); // unrelated student

    $response = $this->actingAs($this->admin)->getJson("/api/students/{$student->id}/courses");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('deletes an enrollment', function () {
    $enrollment = Enrollment::factory()->create();

    $response = $this->actingAs($this->admin)->deleteJson("/api/enrollments/{$enrollment->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
});
