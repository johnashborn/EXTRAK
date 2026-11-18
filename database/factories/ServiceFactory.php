<?php

namespace Database\Factories;

use App\Enums\ServiceState;
use App\Models\Campus;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'campus_id' => Campus::factory(),
            'title' => fake()->sentence(4),
            'state' => ServiceState::Draft,
            'encoded_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['state' => ServiceState::Published, 'published_at' => now()]);
    }
}
