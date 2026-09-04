<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get()
            ->map(function (Plan $plan): array {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'price' => (float) $plan->price,
                    'billing_period' => $plan->billing_period,
                    'removes_ads' => (bool) $plan->removes_ads,
                    'support_sessions' => (int) $plan->support_sessions,
                    'is_addon' => (bool) $plan->is_addon,
                    'is_active' => (bool) $plan->is_active,
                    'sort_order' => (int) $plan->sort_order,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }
}
