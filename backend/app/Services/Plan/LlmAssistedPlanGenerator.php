<?php

namespace App\Services\Plan;

use App\Models\Profile;
use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps the rule-based generator with an optional LLM call used only to produce a
 * friendlier, more personalized rationale/coaching note for the plan. The structural
 * plan itself (days/sets/reps) always comes from the deterministic rule-based engine,
 * which keeps the AI layer cheap, fast, and safe to fail: if no API key is configured
 * or the call errors/times out, we silently fall back to the rule-based plan untouched.
 */
class LlmAssistedPlanGenerator implements PlanGeneratorInterface
{
    public function __construct(private readonly RuleBasedPlanGenerator $base) {}

    public function generate(User $user, Profile $profile, ?WorkoutPlan $previousPlan = null): array
    {
        $plan = $this->base->generate($user, $profile, $previousPlan);

        $apiKey = config('services.openai.api_key');

        if (! $apiKey) {
            return $plan;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(8)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a supportive, concise fitness coach. Respond with 2-3 short sentences only.'],
                        ['role' => 'user', 'content' => sprintf(
                            "Write a short, encouraging note (max 3 sentences) introducing this week's plan to the user. Goal: %s. Experience: %s. Days/week: %d. Plan summary: %s",
                            $profile->goal,
                            $profile->experience_level,
                            $profile->days_per_week,
                            $plan['rationale']['summary'] ?? ''
                        )],
                    ],
                    'max_tokens' => 150,
                ]);

            if ($response->successful()) {
                $message = $response->json('choices.0.message.content');
                if ($message) {
                    $plan['rationale']['coach_note'] = trim($message);
                    $plan['rationale']['engine'] = 'rule_based_v1+llm_note';
                }
            }
        } catch (\Throwable $e) {
            Log::warning('LLM plan rationale generation failed, falling back to rule-based note.', [
                'error' => $e->getMessage(),
            ]);
        }

        return $plan;
    }
}
