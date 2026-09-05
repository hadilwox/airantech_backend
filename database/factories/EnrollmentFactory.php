<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $course = Course::factory()->create();

        return [
            'student_id' => Student::factory(),
            'course_id' => $course->id,
            'enrolled_at' => now()->toDateString(),
            'tuition_amount' => $course->tuition_fee,
            'paid_amount' => 0,
            'status' => EnrollmentStatus::Active,
        ];
    }
}
