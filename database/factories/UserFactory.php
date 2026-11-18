<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::CampusUploader,
            'campus_id' => Campus::factory(),
            'is_active' => true,
            'auth_provider' => 'local',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function president(): static
    {
        return $this->state(fn () => ['role' => Role::President, 'campus_id' => null]);
    }

    public function campusUploader(Campus $campus): static
    {
        return $this->state(fn () => ['role' => Role::CampusUploader, 'campus_id' => $campus->id]);
    }

    public function campusDirector(Campus $campus): static
    {
        return $this->state(fn () => ['role' => Role::CampusDirector, 'campus_id' => $campus->id]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
