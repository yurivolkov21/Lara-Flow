<?php

namespace App\Enums;

enum Locale: string
{
    case English = 'en';
    case Vietnamese = 'vi';

    /**
     * Get the language name, written in that language.
     */
    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Vietnamese => 'Tiếng Việt',
        };
    }

    /**
     * Get the locale codes.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
