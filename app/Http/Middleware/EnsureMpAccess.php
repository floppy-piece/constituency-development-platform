<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class EnsureMpAccess
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Verify token & parse payload
            $payload = JWTAuth::parseToken()->getPayload();

            // Ensure claim confirms MP role
            if ($payload->get('role') !== 'mp') {
                return response()->json(['error' => 'Forbidden: MP access required.'], 403);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Unauthorized or invalid token.'], 401);
        }

        return $next($request);
    }
}