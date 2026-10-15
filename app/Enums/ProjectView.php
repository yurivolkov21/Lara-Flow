<?php

namespace App\Enums;

enum ProjectView: string
{
    case List = 'list';
    case Kanban = 'kanban';

    /**
     * Get the display label for the view.
     */
    public function label(): string
    {
        return match ($this) {
            self::List => __('List'),
            self::Kanban => __('Kanban'),
        };
    }
}
