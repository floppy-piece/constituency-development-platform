<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetAppLanguage
{
    public function handle(Request $request, Closure $next)
    {
        $supported = ['en', 'sw', 'sheng', 'kikuyu', 'luo', 'luhya', 'kalenjin', 'kamba'];

        // 1. Check if language was sent in request ('language' or 'lang')
        $inputLocale = $request->input('language') ?? $request->input('lang');

        if ($inputLocale && in_array(strtolower($inputLocale), $supported)) {
            $locale = strtolower($inputLocale);
            Session::put('app_locale', $locale);
        } else {
            // 2. Otherwise read from session, falling back to config default ('en')
            $locale = Session::get('app_locale', config('app.locale', 'en'));
        }

        // 3. Set global Laravel locale for this request
        App::setLocale($locale);

        return $next($request);
    }
}