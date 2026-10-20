<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\Plan\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function __construct(private readonly PlanService $planService) {}

    /**
     * Create or update the authenticated user's fitness profile from the onboarding
     * questionnaire, then immediately generate their first (or next) AI workout plan.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'goal' => ['required', 'string', 'in:build_muscle,lose_fat,endurance,general_fitness'],
            'experience_level' => ['required', 'string', 'in:beginner,intermediate,advanced'],
            'days_per_week' => ['required', 'integer', 'min:2', 'max:6'],
            'session_duration_minutes' => ['required', 'integer', 'min:10', 'max:120'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['string'],
            'niche' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        $profile = Profile::updateOrCreate(
            ['user_id' => $user->id],
            $validator->validated()
        );

        $plan = $this->planService->generateAndStorePlan($user, $profile);

        return response()->json([
            'profile' => $profile,
            'plan' => $plan,
        ], 201);
    }

    /**
     * Return the authenticated user's current fitness profile.
     */
    public function show(Request $request): JsonResponse
    {
        $profile = Profile::where('user_id', $request->user()->id)->first();

        if (! $profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        return response()->json(['profile' => $profile]);
    }
}
