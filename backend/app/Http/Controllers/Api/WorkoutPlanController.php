<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\WorkoutPlan;
use App\Services\Plan\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutPlanController extends Controller
{
    public function __construct(private readonly PlanService $planService) {}

    /**
     * Return the authenticated user's current active plan with days/exercises.
     */
    public function current(Request $request): JsonResponse
    {
        $plan = WorkoutPlan::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->with('days.exercises')
            ->latest('id')
            ->first();

        if (! $plan) {
            return response()->json(['message' => 'No active plan. Complete onboarding to generate one.'], 404);
        }

        return response()->json(['plan' => $plan]);
    }

    /**
     * Manually trigger regeneration of the next week's plan, adapting to adherence
     * on the current plan. Also used by the scheduled weekly re-adjustment job.
     */
    public function regenerate(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = Profile::where('user_id', $user->id)->first();

        if (! $profile) {
            return response()->json(['message' => 'Complete onboarding before generating a plan.'], 422);
        }

        $plan = $this->planService->generateAndStorePlan($user, $profile);

        return response()->json(['plan' => $plan], 201);
    }

    /**
     * List the authenticated user's plan history.
     */
    public function history(Request $request): JsonResponse
    {
        $plans = WorkoutPlan::where('user_id', $request->user()->id)
            ->with('days.exercises')
            ->orderByDesc('week_number')
            ->get();

        return response()->json(['plans' => $plans]);
    }
}
