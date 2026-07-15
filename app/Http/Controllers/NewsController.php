<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Support\PublicAssetUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $search = trim($validated['q'] ?? '');

        $query = $this->baseQuery($request);

        if ($request->filled('branch_id')) {
            $query->where('user_id', $request->input('branch_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'news' => collect($paginator->items())
                ->map(fn (News $item) => $this->serializeNews($item, $request->user()))
                ->values(),
            'meta' => $this->paginationMeta($paginator),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $branchIds = $request->user()->subscribedBranches()->pluck('users.id');

        $query = $this->baseQuery($request)->whereIn('user_id', $branchIds);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $news = $query
            ->get()
            ->map(fn (News $item) => $this->serializeNews($item, $request->user()));

        return response()->json([
            'news' => $news,
        ]);
    }

    public function saved(Request $request): JsonResponse
    {
        $news = $request->user()
            ->savedNews()
            ->with(['user:id,full_name,avatar', 'category:id,name'])
            ->latest('saved_news.created_at')
            ->get()
            ->map(fn (News $item) => $this->serializeNews($item, $request->user()));

        return response()->json([
            'news' => $news,
        ]);
    }

    public function show(Request $request, News $news): JsonResponse
    {
        $news->load(['user:id,full_name,avatar', 'category:id,name']);

        return response()->json([
            'news' => $this->serializeNews($news, $request->user()),
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $news = $request->user()
            ->news()
            ->with(['user:id,full_name,avatar', 'category:id,name'])
            ->latest()
            ->get()
            ->map(fn (News $item) => $this->serializeNews($item, $request->user()));

        return response()->json([
            'news' => $news,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'link' => ['required', 'url', 'max:2048'],
            'image' => ['required', 'image', 'max:5120'],
            'category_id' => ['required', 'exists:categories,id'],
        ]);

        $path = $request->file('image')->store('news', 'public');

        $news = $request->user()->news()->create([
            'title' => $validated['title'],
            'link' => $validated['link'],
            'image' => $path,
            'category_id' => $validated['category_id'],
        ]);

        $news->load(['user:id,full_name,avatar', 'category:id,name']);

        return response()->json([
            'news' => $this->serializeNews($news, $request->user()),
        ], 201);
    }

    public function update(Request $request, News $news): JsonResponse
    {
        if ($news->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only update your own news.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'link' => ['sometimes', 'url', 'max:2048'],
            'image' => ['sometimes', 'image', 'max:5120'],
            'category_id' => ['sometimes', 'exists:categories,id'],
        ]);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($news->image);
            $news->image = $request->file('image')->store('news', 'public');
        }

        if (array_key_exists('title', $validated)) {
            $news->title = $validated['title'];
        }

        if (array_key_exists('link', $validated)) {
            $news->link = $validated['link'];
        }

        if (array_key_exists('category_id', $validated)) {
            $news->category_id = $validated['category_id'];
        }

        $news->save();
        $news->load(['user:id,full_name,avatar', 'category:id,name']);

        return response()->json([
            'news' => $this->serializeNews($news, $request->user()),
        ]);
    }

    public function destroy(Request $request, News $news): JsonResponse
    {
        if ($news->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only delete your own news.',
            ], 403);
        }

        Storage::disk('public')->delete($news->image);
        $news->delete();

        return response()->json([
            'message' => 'News deleted.',
        ]);
    }

    public function save(Request $request, News $news): JsonResponse
    {
        $request->user()->savedNews()->syncWithoutDetaching([$news->id]);

        return response()->json([
            'message' => 'News saved.',
            'is_saved' => true,
        ]);
    }

    public function unsave(Request $request, News $news): JsonResponse
    {
        $request->user()->savedNews()->detach($news->id);

        return response()->json([
            'message' => 'News removed from saved.',
            'is_saved' => false,
        ]);
    }

    private function baseQuery(Request $request)
    {
        $user = $request->user();

        return News::with(['user:id,full_name,avatar', 'category:id,name'])
            ->when($user, function ($query) use ($user) {
                $query->withExists([
                    'savedByUsers as is_saved' => fn ($savedQuery) => $savedQuery->where('users.id', $user->id),
                ]);
            })
            ->latest();
    }

    private function serializeNews(News $news, ?\App\Models\User $user = null): array
    {
        $isSaved = $user
            ? ($news->is_saved ?? $user->savedNews()->where('news_id', $news->id)->exists())
            : false;

        return [
            'id' => (string) $news->id,
            'title' => $news->title,
            'link' => $news->link,
            'image_url' => PublicAssetUrl::fromStoragePath($news->image),
            'user_id' => (string) $news->user_id,
            'author_name' => $news->user?->full_name ?? '',
            'category_id' => $news->category_id ? (string) $news->category_id : null,
            'category_name' => $news->category?->name ?? null,
            'created_at' => $news->created_at?->toISOString() ?? '',
            'is_saved' => $isSaved,
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
