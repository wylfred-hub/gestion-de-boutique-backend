<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    // ─── GET /api/v1/reports/stock ────────────────────
    public function stock(): JsonResponse
    {
        $filters = request()->only([
            'category_id',
            'low_stock',
            'search',
        ]);

        // Le front attend un objet shape StockReport dans `data`
        // { totalProducts, lowStockProducts, stockValue }
        $resume = $this->reportService->resumeStock();

        return response()->json([
            'success' => true,
            'data'    => [
                'totalProducts'    => (int) ($resume['total_produits'] ?? 0),
                'lowStockProducts' => (int) ($resume['produits_alertes'] ?? 0),
                'stockValue'       => (float) ($resume['valeur_stock_vente'] ?? 0),
            ],
        ], 200);
    }


    // ─── GET /api/v1/reports/sales ────────────────────
    public function sales(): JsonResponse
    {
        $filters = request()->only([
            'date_from',
            'date_to',
            'status',
            'user_id',
            'client_id',
        ]);

        $data   = $this->reportService->rapportVentes($filters);
        $resume = $this->reportService->resumeVentes($filters);

        return response()->json([
            'success' => true,
            'resume'  => $resume,
            'data'    => $data,
            'total'   => $data->count(),
        ], 200);
    }

    // ─── GET /api/v1/reports/movements ────────────────
    public function movements(): JsonResponse
    {
        $filters = request()->only([
            'product_id',
            'type',
            'date_from',
            'date_to',
            'user_id',
        ]);

        $data = $this->reportService->rapportMouvements($filters);

        return response()->json([
            'success' => true,
            'data'    => $data,
            'total'   => $data->count(),
        ], 200);
    }

    // ─── POST /api/v1/reports/export ──────────────────
    public function export(): mixed
    {

        $type   = request('type');    // stock, sales, movements
        $format = request('format');  // csv, pdf

        if (!in_array($type, ['stock', 'sales', 'movements'])) {
            return response()->json([
                'success' => false,
                'message' => 'Type invalide. Valeurs acceptées : stock, sales, movements.',
            ], 422);
        }

        if (!in_array($format, ['csv', 'pdf'])) {
            return response()->json([
                'success' => false,
                'message' => 'Format invalide. Valeurs acceptées : csv, pdf.',
            ], 422);
        }

        $filters = request()->only([
            'date_from',
            'date_to',
            'status',
            'category_id',
            'low_stock',
            'product_id',
            'user_id',
            'client_id',
            'search',
        ]);

        try {
            // ─── Générer les données ───────────────────
            $data = match($type) {
                'stock'     => $this->reportService->rapportStock($filters),
                'sales'     => $this->reportService->rapportVentes($filters),
                'movements' => $this->reportService->rapportMouvements($filters),
            };


            // ─── Export CSV ───────────────────────────
            if ($format === 'csv') {
                $filepath = $this->reportService->exportCsv($type, $data);
                $filename = basename($filepath);

                return response()->download(
                    $filepath,
                    $filename,
                    ['Content-Type' => 'text/csv; charset=UTF-8']
                )->deleteFileAfterSend(true);
            }


            // ─── Export PDF ───────────────────────────
            if ($format === 'pdf') {
                $filepath = match($type) {
                    'stock' => $this->reportService->exportPdfStock(
                        $data,
                        $this->reportService->resumeStock()
                    ),
                    'sales' => $this->reportService->exportPdfVentes(
                        $data,
                        $this->reportService->resumeVentes($filters)
                    ),
                    'movements' => $this->reportService->exportCsv($type, $data),
                };

                $filename = basename($filepath);
                $mime     = $type === 'movements' ? 'text/csv' : 'application/pdf';

                return response()->download(
                    $filepath,
                    $filename,
                    ['Content-Type' => $mime]
                )->deleteFileAfterSend(true);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }

        // fallback (au cas où format/type sortiraient des cas attendus)
        return response()->json([
            'success' => false,
            'message' => 'Requête export invalide.',
        ], 422);
    }

    // ─── POST /api/v1/reports/sales/{id}/export ──────
    public function exportSingleSale(string $id): mixed
    {
        $format = request('format', 'pdf');

        try {
            // Récupérer la vente avec ses relations pour garantir l'accès aux produits
            $sale = \App\Models\Sale::forCurrentOrganization()
                ->with(['items.product', 'client', 'user'])
                ->find($id);

            if (!$sale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vente non trouvée.',
                ], 404);
            }

            if ($format === 'csv') {
                $filepath = $this->reportService->exportCsvSingleSale($sale);
                $mime = 'text/csv';
            } else {
                $filepath = $this->reportService->exportPdfSingleSale($sale);
                $mime = 'application/pdf';
            }

            $filename = basename($filepath);

            return response()->download($filepath, $filename, ['Content-Type' => $mime])
                             ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}