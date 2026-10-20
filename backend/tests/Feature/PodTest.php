<?php

namespace Tests\Feature;

use App\Models\Pod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PodTest extends TestCase
{
    use RefreshDatabase;

    private function onboard(User $user, string $niche = 'busy_professionals'): void
    {
        $this->actingAs($user, 'sanctum')->postJson('/api/profile', [
            'goal' => 'build_muscle',
            'experience_level' => 'beginner',
            'days_per_week' => 3,
            'session_duration_minutes' => 30,
            'equipment' => ['dumbbells'],
            'niche' => $niche,
        ]);
    }

    public function test_a_user_is_auto_matched_into_a_new_pod_for_their_niche(): void
    {
        $user = User::factory()->create();
        $this->onboard($user);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/pods/auto-join');

        $response->assertCreated()->assertJsonPath('pod.niche', 'busy_professionals');
        $this->assertDatabaseHas('pod_members', ['pod_id' => $response->json('pod.id'), 'user_id' => $user->id]);
    }

    public function test_a_second_user_in_the_same_niche_joins_the_existing_pod_until_capacity(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $this->onboard($userA);
        $this->onboard($userB);

        $podA = $this->actingAs($userA, 'sanctum')->postJson('/api/pods/auto-join')->json('pod');
        $podB = $this->actingAs($userB, 'sanctum')->postJson('/api/pods/auto-join')->json('pod');

        $this->assertSame($podA['id'], $podB['id']);
    }

    public function test_a_new_pod_is_created_once_the_existing_one_is_at_capacity(): void
    {
        $pod = Pod::factory()->create(['niche' => 'runners', 'capacity' => 1]);
        $existingMember = User::factory()->create();
        $pod->members()->create(['user_id' => $existingMember->id, 'role' => 'leader', 'joined_at' => now()]);

        $newUser = User::factory()->create();
        $this->onboard($newUser, 'runners');

        $response = $this->actingAs($newUser, 'sanctum')->postJson('/api/pods/auto-join');

        $response->assertCreated();
        $this->assertNotSame($pod->id, $response->json('pod.id'));
    }

    public function test_a_user_cannot_auto_join_twice(): void
    {
        $user = User::factory()->create();
        $this->onboard($user);

        $this->actingAs($user, 'sanctum')->postJson('/api/pods/auto-join')->assertCreated();
        $this->actingAs($user, 'sanctum')->postJson('/api/pods/auto-join')->assertStatus(422);
    }

    public function test_a_pod_member_can_post_and_list_checkins(): void
    {
        $user = User::factory()->create();
        $this->onboard($user);
        $pod = $this->actingAs($user, 'sanctum')->postJson('/api/pods/auto-join')->json('pod');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/pods/{$pod['id']}/checkins", ['message' => 'Done for today!'])
            ->assertCreated();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/pods/{$pod['id']}/checkins");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_non_member_cannot_post_checkins_to_a_pod(): void
    {
        $pod = Pod::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'sanctum')
            ->postJson("/api/pods/{$pod->id}/checkins", ['message' => 'hi'])
            ->assertStatus(403);
    }
}
