<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'due_on' => $this->due_on?->toDateString(),
            'due_time' => $this->due_time,
            'recurrence' => $this->recurrence?->toArray(),
            'position' => $this->position,
            'completed_at' => $this->completed_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'project_id' => $this->project_id,
            'project' => $this->whenLoaded('project', fn (?Project $project) => $project ? [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color->value,
                'icon' => $project->icon,
            ] : null),
            'items' => TaskItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
