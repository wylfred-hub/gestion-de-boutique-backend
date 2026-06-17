<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    // ─── Export détail vente (CSV) ─────────────────────
    public function exportCsvSingleSale(Sale $sale): string
    {
        $sale->loadMissing(['client', 'user', 'items.product']);

        $filename = 'vente_' . ($sale->sale_number ?? $sale->id) . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/exports/' . $filename);

        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        $fp = fopen($filepath, 'w');

        // BOM UTF-8 pour Excel
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($fp, ['Vente', (string) ($sale->sale_number ?? $sale->id)], ';');
        fputcsv($fp, ['Date', (string) ($sale->created_at?->format('d/m/Y H:i') ?? '')], ';');
        fputcsv($fp, ['Client', (string) ($sale->client?->full_name ?? 'Anonyme')], ';');
        fputcsv($fp, ['Vendeur', (string) ($sale->user?->name ?? '')], ';');
        fputcsv($fp, ['Statut', (string) ($sale->status_libelle ?? '')], ';');
        fputcsv($fp, [], ';');

        fputcsv($fp, ['Référence', 'Produit', 'Qté', 'Prix unité', 'Total ligne'], ';');

        foreach ($sale->items as $it) {
            fputcsv($fp, [
                $it->product?->reference ?? '-',
                $it->product?->name ?? '-',
                (int) $it->quantity,
                (float) $it->unit_price,
                (float) $it->total_price,
            ], ';');
        }

        fputcsv($fp, [], ';');
        fputcsv($fp, ['Sous-total', (float) $sale->subtotal], ';');
        fputcsv($fp, ['Total', (float) $sale->total_amount], ';');

        fclose($fp);
        return $filepath;
    }

    // ─── Export détail vente (PDF) ─────────────────────
    public function exportPdfSingleSale(Sale $sale): string
    {
        $sale->loadMissing(['client', 'user', 'items.product']);

        $filename = 'vente_' . ($sale->sale_number ?? $sale->id) . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = storage_path('app/exports/' . $filename);

        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        $html = $this->buildHtmlSingleSale($sale);
        $pdf  = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $pdf->save($filepath);

        return $filepath;
    }

    private function buildHtmlSingleSale(Sale $sale): string
    {
        $generated = now()->format('d/m/Y H:i');

        $client   = $sale->client?->full_name ?? 'Anonyme';
        $vendeur   = $sale->user?->name ?? '-';
        $status    = $sale->status_libelle;
        $subtotal  = number_format((float) $sale->subtotal, 0, ',', ' ');
        $total      = number_format((float) $sale->total_amount, 0, ',', ' ');

        $rows = '';
        foreach ($sale->items as $it) {
            $ref       = $it->product?->reference ?? '-';
            $name      = $it->product?->name ?? '-';
            $qty       = (int) $it->quantity;
            $unit      = number_format((float) $it->unit_price, 0, ',', ' ');
            $lineTotal = number_format((float) $it->total_price, 0, ',', ' ');

            $rows .= "
            <tr>
                <td>{$ref}</td>
                <td>{$name}</td>
                <td>{$qty}</td>
                <td>{$unit}</td>
                <td>{$lineTotal}</td>
            </tr>";
        }

        return "
<!DOCTYPE html>
<html lang='fr'>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: Arial, sans-serif; font-size: 11px; }
    h1 { font-size: 16px; color: #333; margin-bottom: 10px; }
    .meta { background:#f5f5f5; padding:10px; margin-bottom:15px; border:1px solid #eee; }
    table { width:100%; border-collapse: collapse; }
    th { background:#2d3748; color:white; padding:6px 8px; text-align:left; }
    td { padding:5px 8px; border-bottom:1px solid #ddd; }
    .footer { margin-top:15px; font-size:10px; color:#666; }
  </style>
</head>
<body>
  <h1>Détail Vente — {$sale->sale_number}</h1>

  <div class='meta'>
    <div><b>Date :</b> {$sale->created_at?->format('d/m/Y H:i')}</div>
    <div><b>Client :</b> {$client}</div>
    <div><b>Vendeur :</b> {$vendeur}</div>
    <div><b>Statut :</b> {$status}</div>
    <div style='margin-top:8px;'><b>Sous-total :</b> {$subtotal} &nbsp;&nbsp;|&nbsp;&nbsp; <b>Total :</b> {$total} FCFA</div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Référence</th>
        <th>Produit</th>
        <th>Qté</th>
        <th>Prix unité</th>
        <th>Total ligne</th>
      </tr>
    </thead>
    <tbody>{$rows}</tbody>
  </table>

  <div class='footer'>Généré le {$generated} — Cachet App</div>
</body>
</html>";
    }


    // ─── Rapport stock actuel ─────────────────────────
    public function rapportStock(array $filters = []): Collection
    {
        return Product::with('category')
            ->when(
                isset($filters['category_id']),
                fn($q) =>
                $q->where('category_id', $filters['category_id'])
            )
            ->when(
                isset($filters['low_stock']),
                fn($q) =>
                $q->lowStock()
            )
            ->when(
                isset($filters['search']),
                fn($q) =>
                $q->search($filters['search'])
            )
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'reference'      => $p->reference,
                'name'           => $p->name,
                'category'       => $p->category?->name,
                'unit'           => $p->unit,
                'stock_quantity' => $p->stock_quantity,
                'stock_alert'    => $p->stock_alert,
                'purchase_price' => (float) $p->purchase_price,
                'selling_price'  => (float) $p->selling_price,
                'valeur_stock'   => (float) $p->stock_quantity * $p->purchase_price,
                'is_low_stock'   => $p->isLowStock(),
                'is_rupture'     => $p->isOutOfStock(),
            ]);
    }

    // ─── Rapport ventes ───────────────────────────────
    public function rapportVentes(array $filters = []): Collection
    {
        $orgId = request()->header('X-Organization-ID') ?? session('current_organization_id');
        return Sale::where('organization_id', $orgId)->with(['client', 'user', 'items.product'])
            ->when(
                isset($filters['date_from']) && isset($filters['date_to']),
                fn($q) =>
                $q->byDateRange($filters['date_from'], $filters['date_to'])
            )
            ->when(
                isset($filters['status']),
                fn($q) =>
                $q->byStatus($filters['status'])
            )
            ->when(
                isset($filters['user_id']),
                fn($q) =>
                $q->where('user_id', $filters['user_id'])
            )
            ->when(
                isset($filters['client_id']),
                fn($q) =>
                $q->where('client_id', $filters['client_id'])
            )
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($s) => [
                'id'             => $s->id,
                'sale_number'    => $s->sale_number,
                'date'           => $s->created_at?->format('d/m/Y H:i'),
                'client'         => $s->client?->full_name ?? 'Anonyme',
                'vendeur'        => $s->user?->name,
                'status'         => $s->status_libelle,
                'nb_articles'    => $s->items->count(),
                'remise'         => (float) $s->discount_value,
                'total_amount'   => (float) $s->total_amount,
                'delivered_at'   => $s->delivered_at?->format('d/m/Y H:i'),
                'items'          => $s->items->map(fn($it) => [
                    'product_name' => $it->product?->name ?? '-',
                    'quantity'     => (int) $it->quantity,
                    'unit_price'   => (float) $it->unit_price,
                ])->toArray(),
            ]);
    }

    // ─── Rapport mouvements de stock ──────────────────
    public function rapportMouvements(array $filters = []): Collection
    {
        return StockMovement::forCurrentOrganization()
            ->with(['product', 'user'])
            ->when(
                isset($filters['product_id']),
                fn($q) =>
                $q->byProduct($filters['product_id'])
            )
            ->when(
                isset($filters['type']),
                fn($q) =>
                $q->byType($filters['type'])
            )
            ->when(
                isset($filters['date_from']) && isset($filters['date_to']),
                fn($q) =>
                $q->byDateRange($filters['date_from'], $filters['date_to'])
            )
            ->when(
                isset($filters['user_id']),
                fn($q) =>
                $q->where('user_id', $filters['user_id'])
            )
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($m) => [
                'id'              => $m->id,
                'date'            => $m->created_at?->format('d/m/Y H:i'),
                'produit'         => $m->product?->name,
                'reference'       => $m->product?->reference,
                'type'            => $m->type_libelle,
                'quantity'        => $m->quantity,
                'quantity_before' => $m->quantity_before,
                'quantity_after'  => $m->quantity_after,
                'reason'          => $m->reason,
                'utilisateur'     => $m->user?->name,
            ]);
    }


    // ─── Résumé global des ventes ─────────────────────
    public function resumeVentes(array $filters = []): array
    {
        $orgId = request()->header('X-Organization-ID') ?? session('current_organization_id');

        $query = Sale::where('organization_id', $orgId)
            ->whereNotIn('status', [Sale::STATUS_ANNULEE]);

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $query->byDateRange($filters['date_from'], $filters['date_to']);
        }

        return [
            'total_ventes'   => (clone $query)->count(),
            'total_ca'       => (float) (clone $query)->sum('total_amount'),
            'moyenne_panier' => (float) (clone $query)->avg('total_amount'),
            'total_livrees'  => Sale::where('organization_id', $orgId)
                ->where('status', Sale::STATUS_CONFIRMEE)
                ->count(),
            'total_annulees' => Sale::where('organization_id', $orgId)
                ->where('status', Sale::STATUS_ANNULEE)
                ->count(),
        ];
    }


    // ─── Résumé global du stock ───────────────────────
    public function resumeStock(): array
    {
        $products = Product::forCurrentOrganization();

        $total_produits = (clone $products)->whereNull('deleted_at')->count();
        $produits_actifs = (clone $products)->active()->whereNull('deleted_at')->count();
        $produits_alertes = (clone $products)->lowStock()->whereNull('deleted_at')->count();
        $produits_rupture = (clone $products)->where('stock_quantity', 0)->whereNull('deleted_at')->count();

        $valeur_stock_achat = (float) (clone $products)
            ->whereNull('deleted_at')
            ->selectRaw('SUM(stock_quantity * purchase_price) as valeur')
            ->value('valeur');

        $valeur_stock_vente = (float) (clone $products)
            ->whereNull('deleted_at')
            ->selectRaw('SUM(stock_quantity * selling_price) as valeur')
            ->value('valeur');

        return [
            'total_produits'     => $total_produits,
            'produits_actifs'    => $produits_actifs,
            'produits_alertes'   => $produits_alertes,
            'produits_rupture'   => $produits_rupture,
            'valeur_stock_achat' => $valeur_stock_achat,
            'valeur_stock_vente' => $valeur_stock_vente,
        ];
    }

    // ─── Export CSV ───────────────────────────────────
    public function exportCsv(string $type, Collection $data): string
    {
        $filename = $type . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/exports/' . $filename);

        // Créer le dossier exports s'il n'existe pas
        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        $file = fopen($filepath, 'w');

        // BOM UTF-8 pour Excel
        fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Entêtes
        if ($data->isNotEmpty()) {
            fputcsv($file, array_keys($data->first()), ';');
        }

        // Données
        foreach ($data as $row) {
            // On transforme les tableaux imbriqués (items) en texte lisible pour le CSV
            $rowValues = array_map(function ($value) {
                if (is_array($value)) {
                    return collect($value)
                        ->map(fn($i) => ($i['product_name'] ?? '-') . ' (x' . ($i['quantity'] ?? 0) . ')')
                        ->join(' | ');
                }
                return $value;
            }, array_values($row));

            fputcsv($file, $rowValues, ';');
        }

        fclose($file);

        return $filepath;
    }

    /// ─── Export PDF Stock ─────────────────────────────
    public function exportPdfStock(Collection $data, array $resume): string
    {
        $filename = 'stock_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = storage_path('app/exports/' . $filename);

        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        $html = $this->buildHtmlStock($data, $resume);
        $pdf  = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        $pdf->save($filepath);

        return $filepath;
    }

    // ─── Export PDF Ventes ────────────────────────────
    public function exportPdfVentes(Collection $data, array $resume): string
    {
        $filename = 'ventes_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = storage_path('app/exports/' . $filename);

        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        $html = $this->buildHtmlVentes($data, $resume);
        $pdf  = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        $pdf->save($filepath);

        return $filepath;
    }

    // ─── HTML Stock ───────────────────────────────────
    private function buildHtmlStock(Collection $data, array $resume): string
    {
        $rows = '';
        foreach ($data as $row) {
            $statut  = $row['is_rupture'] ? 'Rupture' : ($row['is_low_stock'] ? 'Alerte' : 'OK');
            $color   = $row['is_rupture'] ? '#fed7d7' : ($row['is_low_stock'] ? '#fefcbf' : 'white');
            $rows   .= "
            <tr style='background:{$color}'>
                <td>{$row['reference']}</td>
                <td>{$row['name']}</td>
                <td>{$row['category']}</td>
                <td>{$row['unit']}</td>
                <td>{$row['stock_quantity']}</td>
                <td>{$row['stock_alert']}</td>
                <td>" . number_format($row['purchase_price'], 0, ',', ' ') . "</td>
                <td>" . number_format($row['selling_price'], 0, ',', ' ') . "</td>
                <td>" . number_format($row['valeur_stock'], 0, ',', ' ') . "</td>
                <td>{$statut}</td>
            </tr>";
        }

        $generated        = now()->format('d/m/Y H:i');
        $totalProduits    = $resume['total_produits'];
        $produitsAlertes  = $resume['produits_alertes'];
        $produitsRupture  = $resume['produits_rupture'];
        $valeurAchat      = number_format($resume['valeur_stock_achat'], 0, ',', ' ');
        $valeurVente      = number_format($resume['valeur_stock_vente'], 0, ',', ' ');

        return "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body  { font-family: Arial, sans-serif; font-size: 11px; }
            h1    { font-size: 16px; color: #333; }
            .resume { background: #f5f5f5; padding: 10px; margin-bottom: 15px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th    { background: #2d3748; color: white; padding: 6px 8px; text-align: left; }
            td    { padding: 5px 8px; border-bottom: 1px solid #ddd; }
            .footer { margin-top: 15px; font-size: 10px; color: #666; }
        </style>
    </head>
    <body>
        <h1>Rapport de Stock — {$generated}</h1>
        <div class='resume'>
            <strong>Total produits :</strong> {$totalProduits} &nbsp;|&nbsp;
            <strong>En alerte :</strong> {$produitsAlertes} &nbsp;|&nbsp;
            <strong>En rupture :</strong> {$produitsRupture} &nbsp;|&nbsp;
            <strong>Valeur achat :</strong> {$valeurAchat} FCFA &nbsp;|&nbsp;
            <strong>Valeur vente :</strong> {$valeurVente} FCFA
        </div>
        <table>
            <thead>
                <tr>
                    <th>Référence</th><th>Produit</th><th>Catégorie</th>
                    <th>Unité</th><th>Stock</th><th>Seuil</th>
                    <th>Prix achat</th><th>Prix vente</th>
                    <th>Valeur stock</th><th>Statut</th>
                </tr>
            </thead>
            <tbody>{$rows}</tbody>
        </table>
        <div class='footer'>Généré le {$generated} — Cachet App</div>
    </body>
    </html>";
    }

    // ─── HTML Ventes ──────────────────────────────────
    private function buildHtmlVentes(Collection $data, array $resume): string
    {
        $rows = '';
        foreach ($data as $row) {
            // Détail produits vendus : format "Nom — Qté — Prix total"
            // Note: on s'attend à avoir un tableau `products` (chaque item: name, quantity, total_price)
            // On s'appuie sur la relation `items` (table pivot vente -> produits)
            $produitsDetails = collect($row['items'] ?? [])
                ->map(function ($it) {
                    // Clés exactes : sale_items (quantity, unit_price, total_price)
                    // et produit lié (product.name)
                    $name = $it['product_name']
                        ?? $it['product']?->name
                        ?? '-';

                    $qty = $it['quantity'] ?? 0;
                    $lineTotal = $it['total_price'] ?? (($it['unit_price'] ?? 0) * $qty);

                    return $name . ' — Qté: ' . $qty;
                })
                ->join('<br>');





            $rows .= "
            <tr>
                <td>{$row['sale_number']}</td>
                <td>{$row['date']}</td>
                <td>{$row['client']}</td>
                <td>{$row['vendeur']}</td>
                <td>{$row['nb_articles']}</td>
                <td>" . number_format($row['total_amount'], 0, ',', ' ') . "</td>
                <td>{$produitsDetails}</td>
            </tr>";
        }

        $generated      = now()->format('d/m/Y H:i');
        $totalVentes    = $resume['total_ventes'];
        $totalCa        = number_format($resume['total_ca'], 0, ',', ' ');
        $moyennePanier  = number_format($resume['moyenne_panier'], 0, ',', ' ');
        $totalLivrees   = $resume['total_livrees'];
        $totalAnnulees  = $resume['total_annulees'];


        return "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body  { font-family: Arial, sans-serif; font-size: 11px; }
            h1    { font-size: 16px; color: #333; }
            .resume { background: #f5f5f5; padding: 10px; margin-bottom: 15px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th    { background: #2d3748; color: white; padding: 6px 8px; text-align: left; }
            td    { padding: 5px 8px; border-bottom: 1px solid #ddd; }
            tr:nth-child(even) { background: #f9f9f9; }
            .footer { margin-top: 15px; font-size: 10px; color: #666; }
        </style>
    </head>
    <body>
        <h1>Rapport des Ventes — {$generated}</h1>
        <div class='resume'>
            <strong>Total ventes :</strong> {$totalVentes} &nbsp;|&nbsp;
            <strong>CA Total :</strong> {$totalCa} FCFA &nbsp;|&nbsp;
            <strong>Panier moyen :</strong> {$moyennePanier} FCFA &nbsp;|
            <strong>Détails produits (Nom, Qté, Prix total)</strong>

        </div>
        <table>
            <thead>
                <tr>
                    <th>N° Vente</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Vendeur</th>
                    <th>Articles</th>
                    <th>Total</th>
                    <th>Détails produits</th>
                </tr>
            </thead>
            <tbody>{$rows}</tbody>
        </table>

        <div class='footer'>Généré le {$generated} — Cachet App</div>
    </body>
    </html>";
    }
}
