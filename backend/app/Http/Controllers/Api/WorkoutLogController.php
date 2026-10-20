<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkoutLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkoutLogController extends Controller
{
    /**
     * Log a completed workout session (optionally tied to a plan day), with its
     * individual set/rep entries. This is the core activation/retention event.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workout_plan_id' => ['nullable', 'integer', 'exists:workout_plans,id'],
            'plan_day_id' => ['nullable', 'integer', 'exists:plan_days,id'],
            'logged_date' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'perceived_effort' => ['nullable', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string'],
            'entries' => ['nullable', 'array'],
            'entries.*.exercise_name' => ['required_with:entries', 'string'],
            'entries.*.set_number' => ['nullable', 'integer', 'min:1'],
            'entries.*.reps' => ['nullable', 'integer', 'min:0'],
            'entries.*.weight' => ['nullable', 'numeric', 'min:0'],
            'entries.*.duration_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $user = $request->user();

        $log = DB::transaction(function () use ($data, $user) {
            $log = WorkoutLog::create([
                'user_id' => $user->id,
                'workout_plan_id' => $data['workout_plan_id'] ?? null,
                'plan_day_id' => $data['plan_day_id'] ?? null,
                'logged_date' => $data['logged_date'],
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'perceived_effort' => $data['perceived_effort'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['entries'] ?? [] as $entry) {
                $log->entries()->create($entry);
            }

            return $log->load('entries');
        });

        return response()->json(['log' => $log], 201);
    }

    /**
     * List the authenticated user's workout history, most recent first.
     */
    public function index(Request $request): JsonResponse
    {
        $logs = WorkoutLog::where('user_id', $request->user()->id)
            ->with('entries')
            ->orderByDesc('logged_date')
            ->paginate(20);

        return response()->json($logs);
    }
}
