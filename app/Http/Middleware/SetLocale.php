<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Use the signed-in user's language, or the browser's for guests.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale->value
            ?? $request->getPreferredLanguage(Locale::values())
            ?? config('app.locale');

        App::setLocale($locale);

        return $next($request);
    }
}
