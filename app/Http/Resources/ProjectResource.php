<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
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
            'name' => $this->name,
            'color' => $this->color->value,
            'icon' => $this->icon,
            'default_view' => $this->default_view->value,
            'sort_by' => $this->sort_by->value,
            'position' => $this->position,
            'archived_at' => $this->archived_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'open_tasks_count' => $this->whenCounted('open_tasks_count'),
        ];
    }
}
