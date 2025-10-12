<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pemasok>
 */
class PemasokFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_pemasok' => 'PT ' . $this->faker->company(),
            'narahubung' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'nomor_telepon' => $this->faker->phoneNumber(),
            'alamat' => $this->faker->address(),
            'catatan' => $this->faker->optional()->sentence(),
            'created_by' => 'US001', // Default admin user
        ];
    }

    /**
     * Indicate that the pemasok is active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            // No status field anymore, all are considered active
        ]);
    }

    /**
     * Indicate that the pemasok is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            // No status field anymore
        ]);
    }
}
