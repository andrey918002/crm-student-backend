<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /** Допустимі значення groups.status (узгоджено з API / валідацією). */
    public const STATUSES = ['pending', 'active', 'finished'];

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' '.fake()->randomElement(['A1', 'B1', 'B2', 'C1']),
            'max_students' => fake()->numberBetween(12, 24),
            'progress' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement(self::STATUSES),
            'teacher_id' => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'active']);
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'finished']);
    }
}
