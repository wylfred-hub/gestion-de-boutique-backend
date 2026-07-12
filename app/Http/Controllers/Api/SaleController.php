<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\SaleReturnRequest;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Http\Requests\Sale\UpdateSaleRequest;
use App\Http\Requests\Sale\UpdateSaleStatusRequest;
use App\Http\Resources\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;

class SaleController extends Controller
{
    public function __construct(
        private SaleService  $saleService,
        private StockService $stockService,
    ) {}

    // ─── GET /api/v1/sales ────────────────────────────
    public function index(): JsonResponse
    {
        $sales = Sale::forCurrentOrganization()
            ->with(['client', 'user', 'items.product'])
            ->withCount('items')
            ->when(request('search'), fn($q) =>
                $q->search(request('search'))
            )
            ->when(request('status'), fn($q) =>
                $q->byStatus(request('status'))
            )
            ->when(request('client_id'), fn($q) =>
                $q->where('client_id', request('client_id'))
            )
            ->when(request('user_id'), fn($q) =>
                $q->where('user_id', request('user_id'))
            )
            ->when(request('date_from') && request('date_to'), fn($q) =>
                $q->byDateRange(request('date_from'), request('date_to'))
            )
            ->orderBy('created_at', 'desc')
            ->paginate(request('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => SaleResource::collection($sales),
            'meta'    => [
                'total'        => $sales->total(),
                'per_page'     => $sales->perPage(),
                'current_page' => $sales->currentPage(),
                'last_page'    => $sales->lastPage(),
            ],
        ], 200);
    }

    // ─── POST /api/v1/sales ───────────────────────────
    public function store(StoreSaleRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $currentOrgId = $request->header('X-Organization-ID') ?? session('current_organization_id');
            if (empty($currentOrgId)) {

                return response()->json([
                    'success' => false,
                    'message' => "Organisation courante manquante. Sélectionne une organisation avant de créer une vente.",
                ], 422);
            }

            $data['organization_id'] = $currentOrgId;


            $sale = $this->saleService->creerVente($data);


            return response()->json([
                'success' => true,
                'message' => "Vente {$sale->sale_number} créée avec succès.",
                'data'    => new SaleResource($sale),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ─── GET /api/v1/sales/{id} ───────────────────────
    public function show(int $id): JsonResponse
    {
        $sale = Sale::forCurrentOrganization()
            ->with(['client', 'user', 'items.product'])
            ->find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Vente introuvable.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new SaleResource($sale),
        ], 200);
    }

    // ─── PUT /api/v1/sales/{id} ───────────────────────
    public function update(UpdateSaleRequest $request, int $id): JsonResponse
    {
        $sale = Sale::forCurrentOrganization()->with('items')->find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Vente introuvable.',
            ], 404);
        }

        if (!$sale->isBrouillon()) {
            return response()->json([
                'success' => false,
                'message' => 'Seules les ventes en brouillon peuvent être modifiées.',
            ], 422);
        }

        try {
            $sale = $this->saleService->modifierVente($sale, $request->validated());

            return response()->json([
                'success' => true,
                'message' => "Vente {$sale->sale_number} mise à jour avec succès.",
                'data'    => new SaleResource($sale),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ─── PUT /api/v1/sales/{id}/status ────────────────
    public function updateStatus(UpdateSaleStatusRequest $request, int $id): JsonResponse
    {
        $sale = Sale::forCurrentOrganization()->with('items')->find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Vente introuvable.',
            ], 404);
        }

        try {
            $sale = $this->saleService->changerStatut(
                $sale,
                $request->status,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => "Statut mis à jour : {$sale->status_libelle}.",
                'data'    => new SaleResource($sale),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ─── POST /api/v1/sales/{id}/return ───────────────
    public function returnSale(SaleReturnRequest $request, int $id): JsonResponse
    {
        $sale = Sale::forCurrentOrganization()->with('items')->find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Vente introuvable.',
            ], 404);
        }

        try {
            $retours = $this->saleService->retourVente(
                $sale,
                $request->items,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Retour enregistré avec succès. Stock réintégré.',
                'data'    => $retours,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ─── DELETE /api/v1/sales/{id} ────────────────────
    public function destroy(int $id): JsonResponse
    {
        $sale = Sale::forCurrentOrganization()->with('items')->find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Vente introuvable.',
            ], 404);
        }

        if (!$sale->isBrouillon()) {
            return response()->json([
                'success' => false,
                'message' => 'Seules les ventes en brouillon peuvent être supprimées.',
            ], 422);
        }

        try {
            foreach ($sale->items as $item) {
                $product = Product::find($item->product_id);
                $this->stockService->retour(
                    $product,
                    $item->quantity,
                    'Suppression vente ' . $sale->sale_number,
                    $sale->id,
                    'sale'
                );
            }

            $sale->items()->delete();
            $sale->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vente supprimée avec succès.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}