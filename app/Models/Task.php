<?php

namespace App\Models;

use App\Casts\AsRecurrence;
use App\Data\Recurrence;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $project_id
 * @property int|null $created_by
 * @property string $title
 * @property string|null $description
 * @property TaskStatus $status
 * @property TaskPriority $priority
 * @property CarbonImmutable|null $due_on
 * @property string|null $due_time
 * @property Recurrence|null $recurrence
 * @property int|null $next_task_id
 * @property int $position
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Team $team
 * @property-read Project|null $project
 * @property-read User|null $creator
 * @property-read Task|null $nextTask
 * @property-read Collection<int, TaskItem> $items
 */
#[Fillable([
    'project_id',
    'created_by',
    'title',
    'description',
    'status',
    'priority',
    'due_on',
    'due_time',
    'recurrence',
    'next_task_id',
    'position',
    'completed_at',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'todo',
        'priority' => 'medium',
        'position' => 0,
    ];

    /**
     * Get the team that owns the task.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the project the task belongs to, if any.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created the task.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the task that was spawned when this recurring task was completed.
     *
     * @return BelongsTo<Task, $this>
     */
    public function nextTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'next_task_id');
    }

    /**
     * Get the task's checklist items.
     *
     * @return HasMany<TaskItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TaskItem::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Scope the query to tasks that are not done.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', TaskStatus::Done->value);
    }

    /**
     * Keep the due date as a plain Y-m-d string in the database, so it compares the same way on SQLite and MySQL.
     *
     * @return Attribute<CarbonImmutable|null, mixed>
     */
    protected function dueOn(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => is_string($value) && $value !== ''
                ? CarbonImmutable::parse($value)->startOfDay()
                : null,
            set: fn (mixed $value) => match (true) {
                $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                is_string($value) && $value !== '' => CarbonImmutable::parse($value)->toDateString(),
                default => null,
            },
        );
    }

    /**
     * Store the due time as H:i:s and read it back as H:i.
     *
     * @return Attribute<string|null, mixed>
     */
    protected function dueTime(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => is_string($value) && $value !== '' ? substr($value, 0, 5) : null,
            set: fn (mixed $value) => is_string($value) && $value !== ''
                ? CarbonImmutable::parse($value)->format('H:i:s')
                : null,
        );
    }

    /**
     * Get the position for a task added to the end of a list or Kanban column.
     */
    public static function nextPosition(int $teamId, ?int $projectId, TaskStatus $status): int
    {
        $max = static::query()
            ->where('team_id', $teamId)
            ->where('project_id', $projectId)
            ->where('status', $status->value)
            ->max('position');

        return is_numeric($max) ? (int) $max + 1 : 0;
    }

    /**
     * Determine if the task is done.
     */
    public function isDone(): bool
    {
        return $this->status === TaskStatus::Done;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'recurrence' => AsRecurrence::class,
            'position' => 'integer',
            'completed_at' => 'datetime',
        ];
    }
}
