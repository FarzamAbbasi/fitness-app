<?php

namespace App\Services\Plan;

use App\Models\Profile;
use App\Models\User;
use App\Models\WorkoutPlan;

interface PlanGeneratorInterface
{
    /**
     * Generate a structured weekly workout plan for the given user/profile.
     *
     * Returned shape:
     * [
     *   'title' => string,
     *   'rationale' => array (free-form explanation data, stored as JSON),
     *   'days' => [
     *     [
     *       'day_number' => int,
     *       'focus' => string,
     *       'notes' => ?string,
     *       'exercises' => [
     *         ['order' => int, 'exercise_name' => string, 'sets' => ?int, 'reps' => ?string,
     *          'duration_seconds' => ?int, 'rest_seconds' => ?int, 'notes' => ?string],
     *       ],
     *     ],
     *   ],
     * ]
     *
     * @param  WorkoutPlan|null  $previousPlan  The prior week's plan + its logs, used for adaptive re-planning.
     */
    public function generate(User $user, Profile $profile, ?WorkoutPlan $previousPlan = null): array;
}
