<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => $this->serializeCategory($category));

        return response()->json([
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        $category = Category::create($validated);

        return response()->json([
            'category' => $this->serializeCategory($category),
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id],
        ]);

        $category->update($validated);

        return response()->json([
            'category' => $this->serializeCategory($category),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json([
            'message' => 'Category deleted.',
        ]);
    }

    private function serializeCategory(Category $category): array
    {
        return [
            'id' => (string) $category->id,
            'name' => $category->name,
        ];
    }
}
