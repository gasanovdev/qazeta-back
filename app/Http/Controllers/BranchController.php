<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PublicAssetUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();
        $perPage = $validated['per_page'] ?? 10;
        $search = trim($validated['q'] ?? '');

        $query = User::query()
            ->where('role', 'branch')
            ->withCount(['news', 'subscribers'])
            ->when($user, function ($builder) use ($user) {
                $builder->withExists([
                    'subscribers as is_subscribed' => fn ($subQuery) => $subQuery->where('users.id', $user->id),
                ]);
            })
            ->when($search !== '', function ($builder) use ($search) {
                $builder->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest();

        $paginator = $query->paginate($perPage);

        return response()->json([
            'branches' => collect($paginator->items())
                ->map(fn (User $branch) => $this->serializeBranch($branch, $user))
                ->values(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    public function show(Request $request, User $branch): JsonResponse
    {
        if ($branch->role !== 'branch') {
            return response()->json([
                'message' => 'Branch not found.',
            ], 404);
        }

        $user = $request->user();
        $branch->loadCount(['news', 'subscribers']);

        if ($user) {
            $branch->is_subscribed = $branch->subscribers()->where('users.id', $user->id)->exists();
        }

        return response()->json([
            'branch' => $this->serializeBranch($branch, $user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:30', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $branch = User::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => $validated['password'],
            'role' => 'branch',
            'interest_ids' => [],
        ]);

        $branch->loadCount(['news', 'subscribers']);

        return response()->json([
            'branch' => $this->serializeBranch($branch, $request->user()),
        ], 201);
    }

    private function serializeBranch(User $branch, ?User $user = null): array
    {
        $isSubscribed = false;

        if ($user && isset($branch->is_subscribed)) {
            $isSubscribed = (bool) $branch->is_subscribed;
        } elseif ($user) {
            $isSubscribed = $branch->subscribers()->where('users.id', $user->id)->exists();
        }

        return [
            'id' => (string) $branch->id,
            'full_name' => $branch->full_name,
            'email' => $branch->email,
            'phone_number' => $branch->phone_number ?? '',
            'avatar_url' => PublicAssetUrl::fromStoragePath($branch->avatar),
            'news_count' => $branch->news_count ?? 0,
            'subscribers_count' => $branch->subscribers_count ?? 0,
            'is_subscribed' => $isSubscribed,
        ];
    }

    private function paginationMeta($paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'has_more' => $paginator->hasMorePages(),
        ];
    }
}
