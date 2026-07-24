<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInputs
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$value, $key) {
            if (is_string($value)) {
                // Remove potential XSS script execution tags
                $value = strip_tags($value);

                // Neutralize Command Injection characters if passed in text inputs
                $value = preg_replace('/[;&|`$><]/', '', $value);

                // Trim whitespace
                $value = trim($value);
            }
        });

        $request->merge($input);

        return $next($request);
    }
}
