<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pod;
use App\Models\PodCheckin;
use App\Models\PodMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PodCheckinController extends Controller
{
    /**
     * Post a check-in (message, optionally tied to a logged workout) to a pod the
     * user belongs to. This drives the accountability/community engagement loop.
     */
    public function store(Request $request, Pod $pod): JsonResponse
    {
        $user = $request->user();

        if (! PodMember::where('pod_id', $pod->id)->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are not a member of this pod'], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => ['nullable', 'string', 'max:1000'],
            'workout_log_id' => ['nullable', 'integer', 'exists:workout_logs,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $checkin = PodCheckin::create([
            'pod_id' => $pod->id,
            'user_id' => $user->id,
            'workout_log_id' => $validator->validated()['workout_log_id'] ?? null,
            'message' => $validator->validated()['message'] ?? null,
        ]);

        return response()->json(['checkin' => $checkin->load('user')], 201);
    }

    /**
     * List recent check-ins for a pod the user belongs to.
     */
    public function index(Request $request, Pod $pod): JsonResponse
    {
        if (! PodMember::where('pod_id', $pod->id)->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'You are not a member of this pod'], 403);
        }

        $checkins = $pod->checkins()->with('user')->paginate(30);

        return response()->json($checkins);
    }
}
