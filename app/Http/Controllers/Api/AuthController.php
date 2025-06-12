<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        
        $user = User::create($validated);
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        
        if (!Auth::attempt([$loginField => $credentials['login'], 'password' => $credentials['password']])) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function user(): JsonResponse
    {
        $user = Auth::user();

        $profilePictureUrl = null;
    
        if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
            $profilePictureUrl = Storage::url($user->profile_picture);
        }
    
        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'profile_picture_url' => $profilePictureUrl,
        ]);
    }

    public function logout(): JsonResponse
    {
        Auth::user()->tokens()->delete();
        
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
