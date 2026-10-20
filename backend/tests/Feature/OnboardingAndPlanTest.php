<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkoutLog;
use App\Models\WorkoutPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingAndPlanTest extends TestCase
{
    use RefreshDatabase;

    private function onboardingPayload(array $overrides = []): array
    {
        return array_merge([
            'goal' => 'build_muscle',
            'experience_level' => 'beginner',
            'days_per_week' => 3,
            'session_duration_minutes' => 30,
            'equipment' => ['dumbbells'],
            'niche' => 'busy_professionals',
        ], $overrides);
    }

    public function test_onboarding_creates_a_profile_and_generates_a_starter_plan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile', $this->onboardingPayload());

        $response->assertCreated()
            ->assertJsonPath('profile.goal', 'build_muscle')
            ->assertJsonPath('plan.week_number', 1)
            ->assertJsonPath('plan.status', 'active')
            ->assertJsonCount(7, 'plan.days');

        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'goal' => 'build_muscle']);
        $this->assertDatabaseHas('workout_plans', ['user_id' => $user->id, 'week_number' => 1, 'status' => 'active']);
    }

    public function test_onboarding_validation_rejects_invalid_goal(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/profile', $this->onboardingPayload(['goal' => 'not_a_real_goal']));

        $response->assertStatus(422)->assertJsonValidationErrors(['goal']);
    }

    public function test_generated_plan_only_uses_available_equipment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/profile', $this->onboardingPayload([
            'equipment' => [], // bodyweight only
        ]));

        $plan = WorkoutPlan::where('user_id', $user->id)->with('days.exercises')->first();

        $exerciseNames = $plan->days->flatMap(fn ($day) => $day->exercises->pluck('exercise_name'));

        $this->assertNotEmpty($exerciseNames);
        // None of the generated exercises should require dumbbells/barbell since none were available.
        $this->assertFalse($exerciseNames->contains(fn ($name) => str_contains($name, 'Dumbbell')));
        $this->assertFalse($exerciseNames->contains(fn ($name) => str_contains($name, 'Barbell')));
    }

    public function test_user_can_fetch_their_current_active_plan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/profile', $this->onboardingPayload());

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/plan');

        $response->assertOk()->assertJsonPath('plan.status', 'active');
    }

    public function test_fetching_plan_before_onboarding_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/plan')->assertStatus(404);
    }

    public function test_regenerating_plan_archives_the_previous_plan_and_increments_week_number(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/profile', $this->onboardingPayload());

        $firstPlan = WorkoutPlan::where('user_id', $user->id)->first();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/plan/regenerate');

        $response->assertCreated()->assertJsonPath('plan.week_number', 2);

        $this->assertDatabaseHas('workout_plans', ['id' => $firstPlan->id, 'status' => 'completed']);
        $this->assertDatabaseCount('workout_plans', 2);
    }

    public function test_plan_regeneration_adapts_based_on_logged_adherence(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/profile', $this->onboardingPayload());

        $plan = WorkoutPlan::where('user_id', $user->id)->first();

        // Log every scheduled (non-rest) day => full adherence => next week should increase volume.
        foreach ($plan->days()->where('focus', '!=', 'Rest')->get() as $day) {
            WorkoutLog::create([
                'user_id' => $user->id,
                'workout_plan_id' => $plan->id,
                'plan_day_id' => $day->id,
                'logged_date' => now(),
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/plan/regenerate');

        $response->assertCreated();
        $this->assertStringContainsString(
            'Increased volume',
            $response->json('plan.ai_rationale.adjustment')
        );
    }
}
