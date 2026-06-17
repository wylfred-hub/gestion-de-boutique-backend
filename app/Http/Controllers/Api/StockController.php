<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StockAdjustmentRequest;
use App\Http\Requests\Stock\StockEntryRequest;
use App\Http\Requests\Stock\StockLossRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    public function __construct(
        private StockService $stockService
    ) {}

    // ─── GET /api/v1/stock/movements ──────────────────
    public function movements(): JsonResponse
    {
        $movements = StockMovement::forCurrentOrganization()->with(['product', 'user'])
            ->when(request('product_id'), fn($q) =>
                $q->byProduct(request('product_id'))
            )
            ->when(request('type'), fn($q) =>
                $q->byType(request('type'))
            )
            ->when(request('date_from') && request('date_to'), fn($q) =>
                $q->byDateRange(request('date_from'), request('date_to'))
            )
            ->when(request('user_id'), fn($q) =>
                $q->where('user_id', request('user_id'))
            )
            ->orderBy('created_at', 'desc')
            ->paginate(request('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => StockMovementResource::collection($movements),
            'meta'    => [
                'total'        => $movements->total(),
                'per_page'     => $movements->perPage(),
                'current_page' => $movements->currentPage(),
                'last_page'    => $movements->lastPage(),
            ],
        ], 200);
    }

    // ─── GET /api/v1/stock/movements/{product_id} ─────
    public function productMovements(int $productId): JsonResponse
    {
        $product = Product::forCurrentOrganization()->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable.',
            ], 404);
        }

        $movements = StockMovement::forCurrentOrganization()->with(['product', 'user'])
            ->byProduct($productId)
            ->when(request('type'), fn($q) =>
                $q->byType(request('type'))
            )
            ->when(request('date_from') && request('date_to'), fn($q) =>
                $q->byDateRange(request('date_from'), request('date_to'))
            )
            ->orderBy('created_at', 'desc')
            ->paginate(request('per_page', 20));

        return response()->json([
            'success' => true,
            'product' => [
                'id'             => $product->id,
                'name'           => $product->name,
                'reference'      => $product->reference,
                'stock_quantity' => $product->stock_quantity,
                'stock_alert'    => $product->stock_alert,
                'is_low_stock'   => $product->isLowStock(),
            ],
            'data' => StockMovementResource::collection($movements),
            'meta' => [
                'total'        => $movements->total(),
                'per_page'     => $movements->perPage(),
                'current_page' => $movements->currentPage(),
                'last_page'    => $movements->lastPage(),
            ],
        ], 200);
    }

    // ─── POST /api/v1/stock/entry ─────────────────────
    public function entry(StockEntryRequest $request): JsonResponse
    {
        $product = Product::forCurrentOrganization()->find($request->product_id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable.',
            ], 404);
        }

        try {
            $movement = $this->stockService->entree(
                $product,
                $request->quantity,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => "Stock mis à jour. +{$request->quantity} {$product->unit}.",
                'data'    => new StockMovementResource(
                    $movement->load(['product', 'user'])
                ),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ─── POST /api/v1/stock/adjustment ────────────────
    public function adjustment(StockAdjustmentRequest $request): JsonResponse
    {
        $product = Product::forCurrentOrganization()->find($request->product_id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable.',
            ], 404);
        }

        try {
            $oldQuantity = $product->stock_quantity;

            $movement = $this->stockService->ajustement(
                $product,
                $request->new_quantity,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => "Stock ajusté de {$oldQuantity} à {$request->new_quantity} {$product->unit}.",
                'data'    => new StockMovementResource(
                    $movement->load(['product', 'user'])
                ),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ─── POST /api/v1/stock/loss ──────────────────────
    public function loss(StockLossRequest $request): JsonResponse
    {
        $product = Product::forCurrentOrganization()->find($request->product_id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable.',
            ], 404);
        }

        // Vérifier stock suffisant
        if (!$this->stockService->verifierDisponibilite($product, $request->quantity)) {
            return response()->json([
                'success' => false,
                'message' => "Stock insuffisant. Stock actuel : {$product->stock_quantity} {$product->unit}.",
            ], 422);
        }

        try {
            $movement = $this->stockService->perte(
                $product,
                $request->quantity,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => "Perte enregistrée. -{$request->quantity} {$product->unit}.",
                'data'    => new StockMovementResource(
                    $movement->load(['product', 'user'])
                ),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}