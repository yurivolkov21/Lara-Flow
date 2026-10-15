<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class Translations
{
    /**
     * Get the JSON translations for the given locale, keyed by the English source text.
     *
     * @return array<string, string>
     */
    public static function for(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! File::exists($path)) {
            return [];
        }

        $translations = json_decode(File::get($path), true);

        return is_array($translations)
            ? array_filter($translations, fn ($value, $key) => is_string($key) && is_string($value), ARRAY_FILTER_USE_BOTH)
            : [];
    }
}
