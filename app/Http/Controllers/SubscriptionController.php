<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function subscribe(Request $request, User $branch): JsonResponse
    {
        if ($branch->role !== 'branch') {
            return response()->json([
                'message' => 'Branch not found.',
            ], 404);
        }

        if ($request->user()->id === $branch->id) {
            return response()->json([
                'message' => 'You cannot subscribe to yourself.',
            ], 422);
        }

        $request->user()->subscribedBranches()->syncWithoutDetaching([$branch->id]);

        return response()->json([
            'message' => 'Subscribed.',
            'subscribers_count' => $branch->subscribers()->count(),
            'is_subscribed' => true,
        ]);
    }

    public function unsubscribe(Request $request, User $branch): JsonResponse
    {
        if ($branch->role !== 'branch') {
            return response()->json([
                'message' => 'Branch not found.',
            ], 404);
        }

        $request->user()->subscribedBranches()->detach($branch->id);

        return response()->json([
            'message' => 'Unsubscribed.',
            'subscribers_count' => $branch->subscribers()->count(),
            'is_subscribed' => false,
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $branches = $request->user()
            ->subscribedBranches()
            ->where('role', 'branch')
            ->withCount(['news', 'subscribers'])
            ->latest('branch_subscriptions.created_at')
            ->get()
            ->map(fn (User $branch) => [
                'id' => (string) $branch->id,
                'full_name' => $branch->full_name,
                'subscribers_count' => $branch->subscribers_count ?? 0,
                'news_count' => $branch->news_count ?? 0,
            ]);

        return response()->json([
            'subscriptions' => $branches,
        ]);
    }
}
