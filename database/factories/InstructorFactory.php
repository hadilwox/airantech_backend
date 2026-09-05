<?php

namespace Database\Factories;

use App\Enums\InstructorStatus;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Instructor>
 */
class InstructorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'father_name' => fake()->firstName('male'),
            'national_id' => fake()->unique()->numerify('##########'),
            'residence' => fake()->address(),
            'marital_status' => fake()->randomElement(['مجرد', 'متاهل']),
            'date_of_birth' => fake()->date(),
            'mobile_phone' => fake()->unique()->numerify('09#########'),
            'landline_phone' => fake()->numerify('0##########'),
            'emergency_phone' => fake()->numerify('09#########'),
            'education_degree' => fake()->randomElement(['کارشناسی', 'کارشناسی ارشد', 'دکتری']),
            'expected_salary' => fake()->numberBetween(50, 500) * 1_000_000,
            'skills' => fake()->sentence(),
            'work_experience' => fake()->paragraph(),
            'status' => InstructorStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InstructorStatus::Approved,
        ]);
    }
}
