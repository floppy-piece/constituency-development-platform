<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get authenticated MP profile details
     */
    public function show(): JsonResponse
    {
        $mp = Auth::guard('mp_api')->user();

        return response()->json([
            'status' => 'success',
            'mp_info' => $mp
        ]);
    }

    /**
     * Update MP Profile Details
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var \App\Models\Mp $mp */
        $mp = Auth::guard('mp_api')->user();

        $validator = Validator::make($request->all(), [
            'mp_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:mps,email,' . ($mp->mp_id ?? $mp->id) . ',mp_id',
            'constituency_name' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $mp->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'mp_info' => $mp
        ]);
    }

    /**
     * Change User Password securely
     */
    public function changePassword(Request $request): JsonResponse
    {
        /** @var \App\Models\Mp $mp */
        $mp = Auth::guard('mp_api')->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->current_password, $mp->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The provided current password does not match our records.'
            ], 400);
        }

        $mp->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully.'
        ]);
    }
}