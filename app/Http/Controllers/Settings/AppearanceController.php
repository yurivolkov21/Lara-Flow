<?php

namespace App\Http\Controllers\Settings;

use App\Enums\AccentColor;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppearanceController extends Controller
{
    /**
     * Save the user's accent color. Light and dark mode stay in a cookie, as in the starter kit.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'accent_color' => ['required', Rule::enum(AccentColor::class)],
        ]);

        $request->user()->update($validated);

        return back();
    }
}
