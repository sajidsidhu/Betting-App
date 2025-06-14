<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use App\Models\SubscriptionLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::where('status', true)->get();
        return response()->json(['plans' => $plans]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $user = $request->user();
        $plan = Plan::findOrFail($request->plan_id);

        try {
            // Log the subscription request
            $log = SubscriptionLog::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
                'request_data' => $request->all()
            ]);

            // Calculate expiration date
            $expiryDate = null;
            if ($user->subscription_expires_at && $user->subscription_expires_at > now()) {
                // If user has an active subscription, extend it
                $expiryDate = Carbon::parse($user->subscription_expires_at)
                    ->addDays($plan->expiry_duration);
            } else {
                // New subscription
                $expiryDate = Carbon::now()->addDays($plan->expiry_duration);
            }

            // Update user subscription
            $user->update([
                'plan_id' => $plan->id,
                'subscribed_at' => now(),
                'subscription_expires_at' => $expiryDate
            ]);

            // Update log status
            $log->update(['status' => 'success']);

            return response()->json([
                'message' => 'Subscription successful',
                'subscription' => [
                    'plan' => $plan->title,
                    'subscribed_at' => $user->subscribed_at,
                    'expires_at' => $user->subscription_expires_at
                ]
            ]);

        } catch (\Exception $e) {
            // Log the error
            if (isset($log)) {
                $log->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            return response()->json([
                'message' => 'Subscription failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
