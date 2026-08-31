<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang') ?? $request->query('locale');

        if ($locale && in_array($locale, ['id', 'en'])) {
            Session::put('locale', $locale);
            cookie()->queue('locale', $locale, 60 * 24 * 365);
        } else {
            $locale = Session::get('locale') ?? $request->cookie('locale');
        }

        if (!$locale || !in_array($locale, ['id', 'en'])) {
            $locale = config('app.locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
