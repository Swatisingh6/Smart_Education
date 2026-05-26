<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' School',
            'type' => $this->faker->randomElement(['Government', 'Semi-Government', 'Private']),
            'area_type' => $this->faker->randomElement(['Urban', 'Rural']),
            'district' => $this->faker->randomElement(['Jaipur', 'Udaipur', 'Jodhpur', 'Kota', 'Ajmer', 'Bikaner', 'Tonk', 'Sikar']),
            'pincode' => $this->faker->postcode(),
        ];
    }
}
