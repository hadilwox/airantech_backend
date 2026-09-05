<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
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
            'student_code' => null,
            'national_id' => fake()->unique()->numerify('##########'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'father_name' => fake()->firstName('male'),
            'date_of_birth' => fake()->date(),
            'mobile_phone' => fake()->unique()->numerify('09#########'),
            'landline_phone' => fake()->numerify('0##########'),
            'emergency_phone' => fake()->numerify('09#########'),
            'education_level' => fake()->randomElement(['دیپلم', 'کاردانی', 'کارشناسی', 'کارشناسی ارشد']),
            'address' => fake()->address(),
            'postal_code' => fake()->postcode(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
