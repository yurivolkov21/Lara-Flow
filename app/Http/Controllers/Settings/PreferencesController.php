<?php

namespace App\Http\Controllers\Settings;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PreferencesController extends Controller
{
    /**
     * Show the user's language, timezone and email digest settings.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/preferences', [
            'timezones' => DateTimeZone::listIdentifiers(),
            'locales' => array_map(
                fn (Locale $locale) => ['value' => $locale->value, 'label' => $locale->label()],
                Locale::cases(),
            ),
        ]);
    }

    /**
     * Update the user's preferences.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'timezone' => ['required', 'timezone:all'],
            'locale' => ['required', Rule::enum(Locale::class)],
            'digest_enabled' => ['required', 'boolean'],
            'digest_time' => ['required', 'date_format:H:i'],
        ]);

        $user = $request->user();
        $user->update($validated);

        app()->setLocale($user->locale->value);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Preferences updated.')]);

        return to_route('preferences.edit');
    }
}
