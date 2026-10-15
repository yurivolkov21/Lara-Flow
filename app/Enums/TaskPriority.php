<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    /**
     * Get the display label for the priority.
     */
    public function label(): string
    {
        return match ($this) {
            self::Low => __('Low'),
            self::Medium => __('Medium'),
            self::High => __('High'),
        };
    }

    /**
     * Get the sort weight for the priority. Higher numbers sort first.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
        };
    }

    /**
     * Get the priorities as options for the frontend.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $priority) => ['value' => $priority->value, 'label' => $priority->label()],
            self::cases(),
        );
    }
}
