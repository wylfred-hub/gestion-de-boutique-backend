<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // ─── GET /api/v1/products ─────────────────────────
    public function index(): JsonResponse
    {
        $products = Product::forCurrentOrganization()
            ->with('category')
            ->when(
                request('search'),
                fn($q) =>
                $q->search(request('search'))
            )
            ->when(
                request('category_id'),
                fn($q) =>
                $q->where('category_id', request('category_id'))
            )
            ->when(
                request('is_active'),
                fn($q) =>
                $q->where('is_active', request('is_active'))
            )
            ->when(
                request('low_stock'),
                fn($q) =>
                $q->lowStock()
            )
            ->orderBy(
                request('sort_by', 'created_at'),
                request('sort_order', 'desc')
            )
            ->paginate(request('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => ProductResource::collection($products),
            'meta'    => [
                'total'        => $products->total(),
                'per_page'     => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
            ],
        ], 200);
    }

    // ─── GET /api/v1/products/low-stock ───────────────
    public function lowStock(): JsonResponse
    {
        $products = Product::forCurrentOrganization()
            ->with('category')
            ->lowStock()
            ->active()
            ->orderBy('stock_quantity', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => ProductResource::collection($products),
            'total'   => $products->count(),
        ], 200);
    }

    // ─── POST /api/v1/products ────────────────────────
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->except('image');

        $currentOrgId = $request->header('X-Organization-ID') ?? session('current_organization_id');
        if (empty($currentOrgId)) {
            return response()->json([
                'success' => false,
                'message' => "Organisation courante manquante. Sélectionne une organisation avant de créer un produit.",
            ], 422);
        }

        $data['organization_id'] = $currentOrgId;

        // Upload image
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')
                ->store('products', 'public');
        }

        $product = Product::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès.',
            'data'    => new ProductResource(
                $product->load('category')
            ),
        ], 201);
    }

    // ─── GET /api/v1/products/{id} ────────────────────
    public function show(int $id): JsonResponse
    {
        $product = Product::forCurrentOrganization()
            ->with('category')
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new ProductResource($product),
        ], 200);
    }

    // ─── PUT /api/v1/products/{id} ────────────────────
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = Product::forCurrentOrganization()->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable.',
            ], 404);
        }

        $data = $request->except('image');

        // Upload nouvelle image & suppression de l'ancienne
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')
                ->store('products', 'public');
        }

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Produit mis à jour avec succès.',
            'data'    => new ProductResource(
                $product->fresh()->load('category')
            ),
        ], 200);
    }

    // ─── DELETE /api/v1/products/{id} ─────────────────
    public function destroy(int $id): JsonResponse
    {
        $product = Product::forCurrentOrganization()->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable.',
            ], 404);
        }

        // Bloquer si lié à des ventes actives
        $activeSales = $product->saleItems()
            ->whereHas(
                'sale',
                fn($q) =>
                $q->whereNotIn('status', ['annulee', 'livree'])
            )->exists();

        if ($activeSales) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un produit lié à des ventes en cours.',
            ], 422);
        }

        $product->delete(); // soft delete

        return response()->json([
            'success' => true,
            'message' => 'Produit supprimé avec succès.',
        ], 200);
    }

    // ─── PUT /api/v1/products/{id}/toggle-active ──────
    public function toggleActive(int $id): JsonResponse
    {
        $product = Product::forCurrentOrganization()->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable.',
            ], 404);
        }

        $product->update(['is_active' => !$product->is_active]);

        $statut = $product->is_active ? 'activé' : 'désactivé';

        return response()->json([
            'success' => true,
            'message' => "Produit {$statut} avec succès.",
            'data'    => new ProductResource(
                $product->fresh()->load('category')
            ),
        ], 200);
    }
}
