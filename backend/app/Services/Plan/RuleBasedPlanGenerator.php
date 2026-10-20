<?php

namespace App\Services\Plan;

use App\Models\Profile;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Models\WorkoutPlan;

/**
 * Deterministic, rules-based workout plan generator built on standard exercise-science
 * periodization heuristics (no external API required). This is the default/fallback
 * generator and is always used by tests since it has no network dependency.
 */
class RuleBasedPlanGenerator implements PlanGeneratorInterface
{
    /**
     * Exercise library grouped by muscle focus, filtered later by available equipment.
     * Each entry: [name, required_equipment] where required_equipment of 'bodyweight' is always available.
     */
    private const EXERCISE_LIBRARY = [
        'Full Body' => [
            ['name' => 'Bodyweight Squat', 'equipment' => 'bodyweight'],
            ['name' => 'Push-Up', 'equipment' => 'bodyweight'],
            ['name' => 'Dumbbell Goblet Squat', 'equipment' => 'dumbbells'],
            ['name' => 'Dumbbell Romanian Deadlift', 'equipment' => 'dumbbells'],
            ['name' => 'Bent-Over Row', 'equipment' => 'dumbbells'],
            ['name' => 'Plank', 'equipment' => 'bodyweight'],
        ],
        'Upper Body' => [
            ['name' => 'Push-Up', 'equipment' => 'bodyweight'],
            ['name' => 'Dumbbell Bench Press', 'equipment' => 'dumbbells'],
            ['name' => 'Dumbbell Row', 'equipment' => 'dumbbells'],
            ['name' => 'Dumbbell Shoulder Press', 'equipment' => 'dumbbells'],
            ['name' => 'Bicep Curl', 'equipment' => 'dumbbells'],
            ['name' => 'Tricep Dip', 'equipment' => 'bodyweight'],
            ['name' => 'Barbell Bench Press', 'equipment' => 'barbell'],
            ['name' => 'Barbell Row', 'equipment' => 'barbell'],
        ],
        'Lower Body' => [
            ['name' => 'Bodyweight Squat', 'equipment' => 'bodyweight'],
            ['name' => 'Walking Lunge', 'equipment' => 'bodyweight'],
            ['name' => 'Dumbbell Goblet Squat', 'equipment' => 'dumbbells'],
            ['name' => 'Dumbbell Romanian Deadlift', 'equipment' => 'dumbbells'],
            ['name' => 'Glute Bridge', 'equipment' => 'bodyweight'],
            ['name' => 'Barbell Squat', 'equipment' => 'barbell'],
            ['name' => 'Barbell Deadlift', 'equipment' => 'barbell'],
            ['name' => 'Calf Raise', 'equipment' => 'bodyweight'],
        ],
        'Push' => [
            ['name' => 'Push-Up', 'equipment' => 'bodyweight'],
            ['name' => 'Dumbbell Bench Press', 'equipment' => 'dumbbells'],
            ['name' => 'Dumbbell Shoulder Press', 'equipment' => 'dumbbells'],
            ['name' => 'Tricep Dip', 'equipment' => 'bodyweight'],
            ['name' => 'Lateral Raise', 'equipment' => 'dumbbells'],
        ],
        'Pull' => [
            ['name' => 'Dumbbell Row', 'equipment' => 'dumbbells'],
            ['name' => 'Bicep Curl', 'equipment' => 'dumbbells'],
            ['name' => 'Resistance Band Pull-Apart', 'equipment' => 'bands'],
            ['name' => 'Superman', 'equipment' => 'bodyweight'],
            ['name' => 'Barbell Row', 'equipment' => 'barbell'],
        ],
        'Legs' => [
            ['name' => 'Bodyweight Squat', 'equipment' => 'bodyweight'],
            ['name' => 'Walking Lunge', 'equipment' => 'bodyweight'],
            ['name' => 'Dumbbell Goblet Squat', 'equipment' => 'dumbbells'],
            ['name' => 'Glute Bridge', 'equipment' => 'bodyweight'],
            ['name' => 'Calf Raise', 'equipment' => 'bodyweight'],
        ],
        'Core & Conditioning' => [
            ['name' => 'Plank', 'equipment' => 'bodyweight'],
            ['name' => 'Mountain Climber', 'equipment' => 'bodyweight'],
            ['name' => 'Bicycle Crunch', 'equipment' => 'bodyweight'],
            ['name' => 'Jumping Jack', 'equipment' => 'bodyweight'],
            ['name' => 'Kettlebell Swing', 'equipment' => 'kettlebells'],
        ],
    ];

    public function generate(User $user, Profile $profile, ?WorkoutPlan $previousPlan = null): array
    {
        $split = $this->resolveSplit($profile->days_per_week);
        $setsRepsRest = $this->resolveSetsRepsRest($profile->goal, $previousPlan, $user);
        $exercisesPerDay = match ($profile->experience_level) {
            'beginner' => 4,
            'advanced' => 6,
            default => 5,
        };

        $equipment = array_merge(['bodyweight'], $profile->equipment ?? []);
        $weekNumber = $previousPlan ? $previousPlan->week_number + 1 : 1;

        $days = [];
        foreach ($split as $dayNumber => $focus) {
            if ($focus === 'Rest') {
                $days[] = [
                    'day_number' => $dayNumber,
                    'focus' => 'Rest',
                    'notes' => 'Active recovery: light walk, stretching, or full rest.',
                    'exercises' => [],
                ];

                continue;
            }

            $pool = array_values(array_filter(
                self::EXERCISE_LIBRARY[$focus] ?? [],
                fn (array $exercise) => in_array($exercise['equipment'], $equipment, true)
            ));

            $selected = array_slice($pool, 0, max($exercisesPerDay, 3));

            $exercises = [];
            foreach ($selected as $i => $exercise) {
                $exercises[] = [
                    'order' => $i + 1,
                    'exercise_name' => $exercise['name'],
                    'sets' => $setsRepsRest['sets'],
                    'reps' => $setsRepsRest['reps'],
                    'duration_seconds' => null,
                    'rest_seconds' => $setsRepsRest['rest_seconds'],
                    'notes' => null,
                ];
            }

            $days[] = [
                'day_number' => $dayNumber,
                'focus' => $focus,
                'notes' => null,
                'exercises' => $exercises,
            ];
        }

        return [
            'title' => sprintf('Week %d: %s Plan', $weekNumber, ucwords(str_replace('_', ' ', $profile->goal))),
            'rationale' => [
                'engine' => 'rule_based_v1',
                'week_number' => $weekNumber,
                'adjustment' => $setsRepsRest['adjustment_reason'],
                'summary' => sprintf(
                    'Generated for a %s targeting %s, training %d day(s)/week using available equipment (%s).',
                    $profile->experience_level,
                    str_replace('_', ' ', $profile->goal),
                    $profile->days_per_week,
                    implode(', ', $equipment)
                ),
            ],
            'days' => $days,
            'week_number' => $weekNumber,
        ];
    }

    /**
     * @return array<int, string> day_number (1-7) => focus label
     */
    private function resolveSplit(int $daysPerWeek): array
    {
        $daysPerWeek = max(2, min(6, $daysPerWeek));

        $templates = [
            2 => [1 => 'Full Body', 2 => 'Rest', 3 => 'Rest', 4 => 'Full Body', 5 => 'Rest', 6 => 'Rest', 7 => 'Rest'],
            3 => [1 => 'Full Body', 2 => 'Rest', 3 => 'Full Body', 4 => 'Rest', 5 => 'Full Body', 6 => 'Rest', 7 => 'Rest'],
            4 => [1 => 'Upper Body', 2 => 'Lower Body', 3 => 'Rest', 4 => 'Upper Body', 5 => 'Lower Body', 6 => 'Rest', 7 => 'Rest'],
            5 => [1 => 'Push', 2 => 'Pull', 3 => 'Legs', 4 => 'Rest', 5 => 'Upper Body', 6 => 'Core & Conditioning', 7 => 'Rest'],
            6 => [1 => 'Push', 2 => 'Pull', 3 => 'Legs', 4 => 'Push', 5 => 'Pull', 6 => 'Legs', 7 => 'Rest'],
        ];

        return $templates[$daysPerWeek];
    }

    /**
     * @return array{sets: int, reps: string, rest_seconds: int, adjustment_reason: string}
     */
    private function resolveSetsRepsRest(string $goal, ?WorkoutPlan $previousPlan, User $user): array
    {
        $base = match ($goal) {
            'build_muscle' => ['sets' => 3, 'reps' => '8-12', 'rest_seconds' => 90],
            'lose_fat' => ['sets' => 3, 'reps' => '12-15', 'rest_seconds' => 45],
            'endurance' => ['sets' => 2, 'reps' => '15-20', 'rest_seconds' => 30],
            default => ['sets' => 3, 'reps' => '10-12', 'rest_seconds' => 60],
        };

        $adjustmentReason = 'Starting plan baseline.';

        if ($previousPlan) {
            $loggedCount = WorkoutLog::where('user_id', $user->id)
                ->where('workout_plan_id', $previousPlan->id)
                ->count();

            $expectedSessions = $previousPlan->days()->where('focus', '!=', 'Rest')->count();

            if ($expectedSessions > 0 && $loggedCount >= $expectedSessions) {
                // Full adherence last week: progress by adding a set.
                $base['sets']++;
                $adjustmentReason = 'Increased volume: you completed all sessions last week.';
            } elseif ($expectedSessions > 0 && $loggedCount < (int) ceil($expectedSessions / 2)) {
                // Low adherence: deload to make the plan easier to stick to.
                $base['rest_seconds'] += 15;
                $adjustmentReason = 'Kept volume steady and added recovery: fewer than half of last week\'s sessions were logged.';
            } else {
                $adjustmentReason = 'Maintained volume: partial adherence to last week\'s plan.';
            }
        }

        return $base + ['adjustment_reason' => $adjustmentReason];
    }
}
