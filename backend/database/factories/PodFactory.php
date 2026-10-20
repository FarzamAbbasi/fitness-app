<?php

namespace Database\Factories;

use App\Models\Pod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pod>
 */
class PodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Pod',
            'niche' => 'general',
            'created_by' => null,
            'capacity' => 15,
            'status' => 'active',
        ];
    }
}
