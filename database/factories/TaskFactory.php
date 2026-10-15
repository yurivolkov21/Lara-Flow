<?php

namespace Database\Factories;

use App\Enums\RecurrenceFrequency;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'title' => fake()->sentence(4),
            'status' => TaskStatus::Todo,
            'priority' => TaskPriority::Medium,
        ];
    }

    /**
     * Put the task in the given project, on the project's team.
     */
    public function inProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => $project->team_id,
            'project_id' => $project->id,
        ]);
    }

    /**
     * Indicate that the task is done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Done,
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the task is due on the given date.
     */
    public function dueOn(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'due_on' => $date,
        ]);
    }

    /**
     * Indicate that the task repeats every day.
     */
    public function daily(int $interval = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'recurrence' => ['frequency' => RecurrenceFrequency::Daily->value, 'interval' => $interval],
        ]);
    }
}
