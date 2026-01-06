<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login staff member
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $staff = Staff::where('username', $request->username)
            ->where('is_deleted', false)
            ->where('status', 'active')
            ->first();

        if (!$staff || !Hash::check($request->password, $staff->password_hash)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Session login for Blade/web routes
        Auth::login($staff);
        $request->session()->regenerate();

        // Revoke existing tokens
        $staff->tokens()->delete();

        // Create new token
        $token = $staff->createToken('auth-token')->plainTextToken;

        $response = response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'role' => $staff->role,
            // 'roles' => $staff->roles, // Removed
            'username' => $staff->username,
            'full_name' => $staff->full_name,
        ]);

        // Set cookie for web authentication (7 days)
        // Important: set explicit path + SameSite so browser sends it on subsequent GET /dashboard
        $response->cookie('sanctum_token', $token, 60 * 24 * 7, '/', null, false, true, false, 'lax');

        return $response;
    }

    /**
     * Logout staff member
     */
    public function logout(Request $request)
    {
        // Revoke current token if present
        if ($request->user() && method_exists($request->user(), 'currentAccessToken') && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = response()->json([
            'message' => 'Logged out successfully',
        ]);

        // Clear the cookie
        $response->cookie('sanctum_token', '', -1, '/');

        if (!$request->expectsJson()) {
            return redirect()->route('login');
        }

        return $response;
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request)
    {
        $staff = $request->user();

        return response()->json([
            'id' => $staff->id,
            'username' => $staff->username,
            'full_name' => $staff->full_name,
            'role' => $staff->role,
            // 'roles' => $staff->roles, // Removed
            'status' => $staff->status,
            'email' => $staff->email,
            'phone' => $staff->phone,
        ]);
    }
}
