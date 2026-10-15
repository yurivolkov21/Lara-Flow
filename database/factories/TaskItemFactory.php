<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskItem>
 */
class TaskItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'title' => fake()->sentence(3),
            'is_done' => false,
        ];
    }

    /**
     * Indicate that the item is done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_done' => true,
        ]);
    }
}
