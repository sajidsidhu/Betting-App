<?php

namespace App\Http\Controllers\Api;

use Throwable;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Traits\HandlesFileUploads;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Controllers\Api\TicketController;
use App\Models\Ticket;

class UserController extends Controller
{
    use HandlesFileUploads;

    /**
     * Update the authenticated user's profile
     */
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
                'id',
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

    /**
     * Delete user account and all associated data
     */
    public function deleteAccount(DeleteAccountRequest $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            // Start transaction
            DB::beginTransaction();

            // 1. Delete all tickets and their associated data
            $ticket_controller = new TicketController();
            $tickets = Ticket::where('user_id', $user->id)->get();
            foreach ($tickets as $ticket) {
                $ticket_controller->delete($ticket);
            }

            $this->deleteFile($user->profile_picture);

            // 3. Revoke all tokens (logout from all devices)
            $user->tokens()->delete();

            // 4. Delete all user sessions
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();

            // 5. Delete the user
            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Account and all associated data deleted successfully'
            ]);

        } catch (Throwable $th) {
            DB::rollBack();
            report($th);

            return response()->json([
                'message' => 'Failed to delete account',
                'error' => config('app.debug') ? $th->getMessage() : null
            ], 500);
        }
    }

    public function user(): JsonResponse
    {
        $user = Auth::user();
        $subscription = null;

        if ($user->plan) {
            $subscription = [
                'plan_title' => $user->plan->title,
                'subscribed_at' => $user->subscribed_at?->format('Y-m-d'),
                'expires_at' => $user->subscription_expires_at?->format('Y-m-d'),
                'is_active' => $user->hasActiveSubscription()
            ];
        }

        return response()->json([
            'user' => $user->only(['id', 'first_name', 'last_name', 'email', 'phone', 'profile_picture']),
            'subscription' => $subscription
        ]);
    }
}
