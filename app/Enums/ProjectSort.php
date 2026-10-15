<?php

namespace App\Enums;

enum ProjectSort: string
{
    case Due = 'due';
    case Priority = 'priority';
    case Manual = 'manual';

    /**
     * Get the display label for the sort order.
     */
    public function label(): string
    {
        return match ($this) {
            self::Due => __('Due date'),
            self::Priority => __('Priority'),
            self::Manual => __('Manual'),
        };
    }
}
