<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // ... existing code ...

    public function update(UserUpdateRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            // Handle profile picture if uploaded
            if ($request->hasFile('profile_picture')) {
                // Delete old profile picture if exists
                if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                    Storage::disk('public')->delete($user->profile_picture);
                }

                // Store new profile picture
                $validated['profile_picture'] = $request->file('profile_picture')
                    ->store('profile_pics', 'public');
            }

            // Update user fields if present
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }
            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }
            if (isset($validated['phone_number'])) {
                $user->phone = $validated['phone_number'];
            }
            if (isset($validated['profile_picture'])) {
                $user->profile_picture = $validated['profile_picture'];
            }

            $user->save();

            // Prepare response
            $response = [
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone,
                'profile_picture_url' => null
            ];

            // Add profile picture URL if exists
            if ($user->profile_picture && Storage::disk('public')->exists($user->profile_picture)) {
                $response['profile_picture_url'] = Storage::url($user->profile_picture);
            }

            return response()->json($response);
        } catch (\Throwable $th) {
            \Log::error($th); // Log the error for debuggin
            return response()->json($th);
        }
        
    }
}