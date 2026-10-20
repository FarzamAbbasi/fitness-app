<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_log_a_workout_with_set_entries(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/workout-logs', [
            'logged_date' => now()->toDateString(),
            'duration_minutes' => 30,
            'perceived_effort' => 7,
            'entries' => [
                ['exercise_name' => 'Push-Up', 'set_number' => 1, 'reps' => 12],
                ['exercise_name' => 'Push-Up', 'set_number' => 2, 'reps' => 10],
            ],
        ]);

        $response->assertCreated()->assertJsonCount(2, 'log.entries');

        $this->assertDatabaseHas('workout_logs', ['user_id' => $user->id, 'duration_minutes' => 30]);
        $this->assertDatabaseCount('workout_log_entries', 2);
    }

    public function test_logging_a_workout_requires_a_logged_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/workout-logs', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['logged_date']);
    }

    public function test_a_user_can_list_their_own_workout_history_only(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/workout-logs', ['logged_date' => now()->toDateString()]);
        $this->actingAs($otherUser, 'sanctum')->postJson('/api/workout-logs', ['logged_date' => now()->toDateString()]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/workout-logs');

        $response->assertOk()->assertJsonCount(1, 'data');
    }
}
