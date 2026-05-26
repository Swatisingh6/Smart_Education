<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'caste' => $this->faker->randomElement(['General', 'OBC', 'SC', 'ST']),
            'date_of_birth' => $this->faker->date('Y-m-d', '-10 years'),
            'standard' => $this->faker->numberBetween(1, 12),
            'status' => 'Enrolled',
            'dropout_reason' => null,
            'dropout_date' => null,
            'area_village_city' => $this->faker->city(),
            'parent_income' => $this->faker->numberBetween(30000, 300000),
            'academic_year' => '2025-2026',
        ];
    }
}
