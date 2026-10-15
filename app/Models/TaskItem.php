<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\TaskItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $task_id
 * @property string $title
 * @property bool $is_done
 * @property int $position
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Task $task
 */
#[Fillable(['title', 'is_done', 'position'])]
class TaskItem extends Model
{
    /** @use HasFactory<TaskItemFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_done' => false,
        'position' => 0,
    ];

    /**
     * Get the task the item belongs to.
     *
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'position' => 'integer',
        ];
    }
}
