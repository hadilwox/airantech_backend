<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Instructor;
use App\Services\CourseCodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = CourseCategory::factory()->create();
        $generated = app(CourseCodeGenerator::class)->generate($category);

        return [
            'category_id' => $category->id,
            'instructor_id' => Instructor::factory(),
            'code' => $generated['code'],
            'jalali_year' => $generated['jalali_year'],
            'jalali_month' => $generated['jalali_month'],
            'title' => fake()->words(3, true),
            'teaching_hours' => fake()->numberBetween(10, 60),
            'capacity' => fake()->numberBetween(10, 30),
            'tuition_fee' => fake()->numberBetween(1_000_000, 10_000_000),
            'instructor_cost' => fake()->numberBetween(500_000, 5_000_000),
            'has_university_certificate' => fake()->boolean(),
            'schedule_days' => fake()->randomElements(
                ['saturday', 'monday', 'wednesday'],
                2
            ),
            'status' => CourseStatus::Upcoming,
        ];
    }
}
