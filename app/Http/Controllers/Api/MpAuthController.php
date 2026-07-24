<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MpAuthController extends Controller
{
    /**
     * Login MP and return JWT token.
     */
    public function login(Request $request)
    {
        // 1. Validate inputs
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:100'],
            'password' => ['required', 'string'],
        ]);

        // 2. Attempt authentication against mp_api guard
        if (! $token = Auth::guard('mp_api')->attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password credentials.',
            ], 401);
        }

        return $this->createNewToken($token);
    }

    /**
     * Get authenticated MP profile.
     */
    public function profile()
    {
        return response()->json([
            'status' => 'success',
            'data' => Auth::guard('mp_api')->user(),
        ]);
    }

    /**
     * Refresh JWT token.
     */
    public function refresh()
    {
        return $this->createNewToken(Auth::guard('mp_api')->refresh());
    }

    /**
     * Invalidate JWT Token (Logout).
     */
    public function logout()
    {
        Auth::guard('mp_api')->logout();

        return response()->json([
            'status' => 'success',
            'message' => 'MP successfully logged out',
        ]);
    }

    /**
     * Format JWT JSON response.
     */
    protected function createNewToken(string $token)
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\Factory $factory */
        $factory = Auth::guard('mp_api')->factory();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $factory->getTTL() * 60, // Expiration time in seconds
            'mp' => Auth::guard('mp_api')->user(),
        ]);
    }
}