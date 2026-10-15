<?php

namespace App\Enums;

/**
 * How many days a team keeps deleted projects and tasks. A null retention on the team means never.
 */
enum TrashRetention: int
{
    case Week = 7;
    case Month = 30;
    case Quarter = 90;

    public const DEFAULT = self::Month;

    /**
     * Get the display label for the retention period.
     */
    public function label(): string
    {
        return __(':count days', ['count' => $this->value]);
    }
}
