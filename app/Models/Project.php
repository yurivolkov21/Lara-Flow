<?php

namespace App\Models;

use App\Enums\ProjectColor;
use App\Enums\ProjectSort;
use App\Enums\ProjectView;
use Carbon\CarbonInterface;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property ProjectColor $color
 * @property string|null $icon
 * @property ProjectView $default_view
 * @property ProjectSort $sort_by
 * @property int $position
 * @property CarbonInterface|null $archived_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Team $team
 * @property-read Collection<int, Task> $tasks
 */
#[Fillable(['name', 'color', 'icon', 'default_view', 'sort_by', 'position', 'archived_at'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'color' => 'slate',
        'default_view' => 'list',
        'sort_by' => 'due',
        'position' => 0,
    ];

    /**
     * Get the team that owns the project.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the project's tasks.
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Scope the query to projects that are not archived.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * Determine if the project is archived.
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'color' => ProjectColor::class,
            'default_view' => ProjectView::class,
            'sort_by' => ProjectSort::class,
            'position' => 'integer',
            'archived_at' => 'datetime',
        ];
    }
}
