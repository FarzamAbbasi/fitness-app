<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pod;
use App\Models\PodMember;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PodController extends Controller
{
    /**
     * List active pods, optionally filtered to the user's niche for discovery.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pod::where('status', 'active')->withCount('members');

        if ($request->filled('niche')) {
            $query->where('niche', $request->string('niche'));
        }

        return response()->json(['pods' => $query->get()]);
    }

    /**
     * Return the authenticated user's current pod(s), with members and recent check-ins.
     */
    public function mine(Request $request): JsonResponse
    {
        $pods = Pod::whereHas('members', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['members.user', 'checkins.user'])
            ->get();

        return response()->json(['pods' => $pods]);
    }

    /**
     * Auto-match the authenticated user into an open pod for their niche, or create
     * a new one if none has capacity. This is called automatically after onboarding
     * so no user is ever left without a pod.
     */
    public function autoJoin(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = Profile::where('user_id', $user->id)->first();
        $niche = $profile->niche ?? 'general';

        if (PodMember::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'User already belongs to a pod'], 422);
        }

        $pod = DB::transaction(function () use ($user, $niche) {
            $pod = Pod::where('niche', $niche)
                ->where('status', 'active')
                ->withCount('members')
                ->get()
                ->first(fn (Pod $p) => $p->members_count < $p->capacity);

            if (! $pod) {
                $pod = Pod::create([
                    'name' => ucfirst(str_replace('_', ' ', $niche)).' Pod #'.(Pod::where('niche', $niche)->count() + 1),
                    'niche' => $niche,
                    'created_by' => $user->id,
                    'capacity' => 15,
                    'status' => 'active',
                ]);
            }

            PodMember::create([
                'pod_id' => $pod->id,
                'user_id' => $user->id,
                'role' => $pod->members()->exists() ? 'member' : 'leader',
                'joined_at' => now(),
            ]);

            return $pod;
        });

        return response()->json(['pod' => $pod->load('members.user')], 201);
    }

    /**
     * Manually join a specific pod (used by the discovery/browse flow).
     */
    public function join(Request $request, Pod $pod): JsonResponse
    {
        $user = $request->user();

        if ($pod->members()->count() >= $pod->capacity) {
            return response()->json(['message' => 'This pod is full'], 422);
        }

        $member = PodMember::firstOrCreate(
            ['pod_id' => $pod->id, 'user_id' => $user->id],
            ['role' => 'member', 'joined_at' => now()]
        );

        return response()->json(['member' => $member], 201);
    }

    /**
     * Leave a pod.
     */
    public function leave(Request $request, Pod $pod): JsonResponse
    {
        PodMember::where('pod_id', $pod->id)->where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Left pod']);
    }
}
