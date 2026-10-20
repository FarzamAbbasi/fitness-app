<?php

namespace App\Services\Plan;

use App\Models\Profile;
use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Support\Facades\DB;

class PlanService
{
    public function __construct(private readonly PlanGeneratorInterface $generator) {}

    /**
     * Generate a new plan for the user (optionally adapting from their current active plan)
     * and persist it, archiving any previous active plan.
     */
    public function generateAndStorePlan(User $user, Profile $profile): WorkoutPlan
    {
        $previousPlan = WorkoutPlan::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        $planData = $this->generator->generate($user, $profile, $previousPlan);

        return DB::transaction(function () use ($user, $planData, $previousPlan) {
            if ($previousPlan) {
                $previousPlan->update(['status' => 'completed']);
            }

            $plan = WorkoutPlan::create([
                'user_id' => $user->id,
                'title' => $planData['title'],
                'status' => 'active',
                'source' => 'ai',
                'week_number' => $planData['week_number'],
                'ai_rationale' => $planData['rationale'],
                'generated_at' => now(),
            ]);

            foreach ($planData['days'] as $dayData) {
                $day = $plan->days()->create([
                    'day_number' => $dayData['day_number'],
                    'focus' => $dayData['focus'],
                    'notes' => $dayData['notes'],
                ]);

                foreach ($dayData['exercises'] as $exerciseData) {
                    $day->exercises()->create($exerciseData);
                }
            }

            return $plan->load('days.exercises');
        });
    }
}
