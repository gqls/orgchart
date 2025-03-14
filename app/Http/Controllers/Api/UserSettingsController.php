<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserSettingsController extends Controller
{
    /**
     * Update two factor authentication
     */
    public function updateTwoFactorAuth(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->enabled) {
            // Enable two-factor authentication
            $user->forceFill([
                'two_factor_secret' => encrypt(random_bytes(32)),
                'two_factor_recovery_codes' => encrypt(json_encode([])),
            ])->save();
        } else {
            // Disable two-factor authentication
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ])->save();
        }

        return response()->json(['message' => 'Two-factor authentication updated']);
    }

    /**
     * List API tokens for the authenticated user
     */
    public function listTokens(Request $request)
    {
        $user = $request->user();
        $tokens = $user->tokens;

        return response()->json($tokens);
    }

    /**
     * Create a new API token
     */
    public function createToken(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'abilities' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Default abilities if none provided
        $abilities = $request->abilities ?? ['read'];

        // Create token
        $token = $user->createToken($request->name, $abilities);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_id' => explode('|', $token->plainTextToken)[0],
        ]);
    }

    /**
     * Revoke an API token
     */
    public function revokeToken(Request $request, $tokenId)
    {
        $user = $request->user();

        // Find and delete the token
        $user->tokens()->where('id', $tokenId)->delete();

        return response()->json(['message' => 'Token revoked successfully']);
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|array',
            'app' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Store notification preferences in user metadata
        $user->notification_preferences = $request->all();
        $user->save();

        return response()->json(['message' => 'Notification preferences updated']);
    }
}