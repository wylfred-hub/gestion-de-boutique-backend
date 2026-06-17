<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    // ─── GET /api/v1/categories ───────────────────────
    public function index(): JsonResponse
    {
        $categories = Category::forCurrentOrganization()
            ->withCount('products')
            ->when(request('search'), fn($q) =>
                $q->where('name', 'like', '%' . request('search') . '%')
            )
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => CategoryResource::collection($categories),
            'total'   => $categories->count(),
        ], 200);
    }

    // ─── POST /api/v1/categories ──────────────────────
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create([
            'organization_id' => auth()->user()->organization_id,
            'name'            => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès.',
            'data'    => new CategoryResource($category),
        ], 201);
    }

    // ─── GET /api/v1/categories/{id} ──────────────────
    public function show(int $id): JsonResponse
    {
        $category = Category::forCurrentOrganization()
            ->withCount('products')
            ->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie introuvable.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new CategoryResource($category),
        ], 200);
    }

    // ─── PUT /api/v1/categories/{id} ──────────────────
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::forCurrentOrganization()->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie introuvable.',
            ], 404);
        }

        $category->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie mise à jour avec succès.',
            'data'    => new CategoryResource($category->fresh()),
        ], 200);
    }

    // ─── DELETE /api/v1/categories/{id} ───────────────
    public function destroy(int $id): JsonResponse
    {
        $category = Category::forCurrentOrganization()
            ->withCount('products')
            ->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie introuvable.',
            ], 404);
        }

        if ($category->hasProducts()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une catégorie qui contient des produits.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès.',
        ], 200);
    }
}