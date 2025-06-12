<?php

namespace App\Http\Controllers\Api;

use Throwable;
use Illuminate\Http\JsonResponse;
use App\Traits\HandlesFileUploads;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UserUpdateRequest;

class UserController extends Controller
{
    use HandlesFileUploads;
    // ... existing code ...

    public function update(UserUpdateRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            // Handle profile picture if uploaded
            if ($request->hasFile('profile_picture')) {
                $validated['profile_picture'] = $this->uploadFile(
                    $request->file('profile_picture'),
                    'profile_pictures',
                    $user->profile_picture
                );
            }

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $user->update($validated);

            return response()->json($user->only([
                'first_name',
                'last_name',
                'phone',
                'email',
                'profile_picture'
            ]));
        } catch (Throwable $th) {
            return response()->json([
                'message' => 'An error occurred while updating your profile.',
                'error' => config('app.debug') ? $th->getMessage() : null
            ], 422);
        }
    }

    
    public function user(): JsonResponse
    {
        $user = Auth::user();
        return response()->json($user->only(['id', 'first_name', 'last_name', 'email', 'phone', 'profile_picture']));
    }
}
