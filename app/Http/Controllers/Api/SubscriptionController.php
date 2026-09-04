<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = Subscription::query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (!$subscription || !$subscription->plan) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_subscription' => false,
                    'subscription_id' => null,
                    'status' => null,
                    'plan' => null,
                    'sessions_remaining' => 0,
                    'removes_ads' => false,
                    'can_book_support_session' => false,
                ],
            ]);
        }

        $plan = $subscription->plan;
        $sessionsRemaining = (int) $subscription->sessions_remaining;

        $canBookSupportSession =
            $subscription->status === 'active'
            && (int) $plan->support_sessions > 0
            && $sessionsRemaining > 0;

        return response()->json([
            'success' => true,
            'data' => [
                'has_subscription' => true,
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'sessions_remaining' => $sessionsRemaining,
                'removes_ads' => (bool) $plan->removes_ads,
                'can_book_support_session' => $canBookSupportSession,
                'starts_at' => optional($subscription->starts_at)?->toISOString(),
                'renews_at' => optional($subscription->renews_at)?->toISOString(),
                'ends_at' => optional($subscription->ends_at)?->toISOString(),
                'plan' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price' => (float) $plan->price,
                    'billing_period' => $plan->billing_period,
                    'removes_ads' => (bool) $plan->removes_ads,
                    'support_sessions' => (int) $plan->support_sessions,
                    'is_addon' => (bool) $plan->is_addon,
                    'is_active' => (bool) $plan->is_active,
                ],
            ],
        ]);
    }
}
