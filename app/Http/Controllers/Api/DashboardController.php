<?php

// namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
// use App\Models\Product;
// use App\Models\Sale;
// use App\Models\StockMovement;
// use Illuminate\Http\JsonResponse;
// use Illuminate\Support\Facades\DB;

// class DashboardController extends Controller
// {
//     // ─── GET /api/v1/dashboard/kpis ───────────────────
//     public function kpis(): JsonResponse
//     {
//         $today     = now()->toDateString();
//         $thisMonth = now()->month;
//         $thisYear  = now()->year;
//         $user      = auth()->user();

//         // Détecter si on affiche la vue globale (Plateforme) pour le SuperAdmin
//         // (aucune organisation sélectionnée dans la session)
//         $isGlobalSA = $user->isSuperAdmin() && !session('current_organization_id');

//         if ($isGlobalSA) {
//             // ─── KPIs PLATEFORME (Revenus des Abonnements) ──
//             // On suppose une table 'subscriptions' avec les colonnes 'amount' et 'status'
//             $caJour = DB::table('subscriptions')->whereDate('created_at', $today)->where('status', 'paid')->sum('amount');
//             $caMois = DB::table('subscriptions')->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->where('status', 'paid')->sum('amount');
//             $caAnnee = DB::table('subscriptions')->whereYear('created_at', $thisYear)->where('status', 'paid')->sum('amount');

//             // Pour le SA, "ventes" peut représenter les nouvelles organisations créées
//             $ventesJour = DB::table('organizations')->whereDate('created_at', $today)->count();
//             $ventesMois = DB::table('organizations')->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count();

//             // Stock pour SA : On affiche des stats sur la croissance du réseau
//             $valeurStock = DB::table('organizations')->count(); // Total Orgs
//             $produitsEnAlerte = DB::table('organizations')->where('is_active', true)->count(); // Orgs actives
//             $produitsRupture = DB::table('users')->where('is_active', true)->count(); // Total utilisateurs

//             // Commandes : Abonnements en attente de validation/paiement
//             $commandesEnAttente = DB::table('subscriptions')->where('status', 'pending')->count();
//             $commandesBrouillon = 0;

//             // Top Organisations par chiffre d'affaires généré pour la plateforme
//             $topProduits = DB::table('organizations')
//                 ->leftJoin('subscriptions', 'organizations.id', '=', 'subscriptions.organization_id')
//                 ->select('organizations.id', 'organizations.name', DB::raw('SUM(subscriptions.amount) as total_ca'))
//                 ->where('subscriptions.status', 'paid')
//                 ->groupBy('organizations.id', 'organizations.name')
//                 ->orderByDesc('total_ca')
//                 ->limit(5)
//                 ->get();
//         } else {
//             // ─── KPIs ORGANISATION (Ventes de Produits) ───
//             $salesBase = Sale::forCurrentOrganization();

//             $caJour = $salesBase
//                 ->whereDate('created_at', $today)
//                 ->whereNotIn('status', [Sale::STATUS_ANNULEE, Sale::STATUS_BROUILLON])
//                 ->sum('total_amount');

//             $caMois = $salesBase
//                 ->whereMonth('created_at', $thisMonth)
//                 ->whereYear('created_at', $thisYear)
//                 ->whereNotIn('status', [Sale::STATUS_ANNULEE, Sale::STATUS_BROUILLON])
//                 ->sum('total_amount');

//             $caAnnee = $salesBase
//                 ->whereYear('created_at', $thisYear)
//                 ->whereNotIn('status', [Sale::STATUS_ANNULEE, Sale::STATUS_BROUILLON])
//                 ->sum('total_amount');

//             // ─── Nombre de ventes ─────────────────────────
//             $ventesJour = $salesBase
//                 ->whereDate('created_at', $today)
//                 ->whereNotIn('status', [Sale::STATUS_ANNULEE, Sale::STATUS_BROUILLON])
//                 ->count();

//             $ventesMois = $salesBase
//                 ->whereMonth('created_at', $thisMonth)
//                 ->whereYear('created_at', $thisYear)
//                 ->whereNotIn('status', [Sale::STATUS_ANNULEE, Sale::STATUS_BROUILLON])
//                 ->count();

//             // ─── Stock ────────────────────────────────────
//             $productsBase = Product::forCurrentOrganization();

//             $valeurStock = $productsBase
//                 ->selectRaw('SUM(stock_quantity * purchase_price) as valeur')
//                 ->whereNull('deleted_at')
//                 ->value('valeur') ?? 0;

//             $produitsEnAlerte = $productsBase
//                 ->lowStock()
//                 ->whereNull('deleted_at')
//                 ->count();

//             $produitsRupture = $productsBase
//                 ->where('stock_quantity', 0)
//                 ->whereNull('deleted_at')
//                 ->count();

//             // ─── Commandes en attente ─────────────────────
//             $commandesEnAttente = $salesBase
//                 ->whereIn('status', [
//                     Sale::STATUS_CONFIRMEE,
//                     Sale::STATUS_EN_PREPARATION,
//                 ])->count();

//             $commandesBrouillon = $salesBase
//                 ->where('status', Sale::STATUS_BROUILLON)
//                 ->count();

//             // ─── Top produits vendus ce mois ──────────────
//             $currentOrgId = session('current_organization_id');

//             $topProduits = DB::table('sale_items')
//                 ->join('products', 'sale_items.product_id', '=', 'products.id')
//                 ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
//                 ->where('sales.organization_id', $currentOrgId)
//                 ->whereMonth('sales.created_at', $thisMonth)
//                 ->whereYear('sales.created_at', $thisYear)
//                 ->whereNotIn('sales.status', [Sale::STATUS_ANNULEE, Sale::STATUS_BROUILLON])
//                 ->where('products.organization_id', $currentOrgId)
//                 ->whereNull('products.deleted_at')
//                 ->select(
//                     'products.id',
//                     'products.name',
//                     'products.reference',
//                     DB::raw('SUM(sale_items.quantity) as total_vendu'),
//                     DB::raw('SUM(sale_items.total_price) as total_ca')
//                 )
//                 ->groupBy('products.id', 'products.name', 'products.reference')
//                 ->orderByDesc('total_vendu')
//                 ->limit(5)
//                 ->get();
//         }

//         return response()->json([
//             'success' => true,
//             'data'    => [
//                 'chiffre_affaires' => [
//                     'jour'  => (float) $caJour,
//                     'mois'  => (float) $caMois,
//                     'annee' => (float) $caAnnee,
//                 ],
//                 'ventes' => [
//                     'jour' => $ventesJour,
//                     'mois' => $ventesMois,
//                 ],
//                 'stock' => [
//                     'valeur_totale'     => (float) $valeurStock,
//                     'produits_en_alerte' => $produitsEnAlerte,
//                     'produits_rupture'   => $produitsRupture,
//                 ],
//                 'commandes' => [
//                     'en_attente' => $commandesEnAttente,
//                     'brouillon'  => $commandesBrouillon,
//                 ],
//                 'top_produits' => $topProduits,
//             ],
//         ], 200);
//     }

//     // ─── GET /api/v1/dashboard/charts/sales ───────────
//     public function chartsSales(): JsonResponse
//     {
//         $period = request('period', 'month'); // day, month, year

//         $data = match($period) {
//             'day'  => $this->ventesParJour(),
//             'year' => $this->ventesParMois(12),
//             default => $this->ventesParJour(30),
//         };

//         return response()->json([
//             'success' => true,
//             'period'  => $period,
//             'data'    => $data,
//         ], 200);
//     }

//     // ─── Ventes par jour (N derniers jours) ───────────
//     private function ventesParJour(int $days = 30): array
//     {
//         $currentOrgId = session('current_organization_id');

//         $results = DB::table('sales')
//             ->selectRaw('DATE(created_at) as date, COUNT(*) as nombre, SUM(total_amount) as montant')
//             ->where('organization_id', $currentOrgId)
//             ->whereNotIn('status', [Sale::STATUS_ANNULEE, Sale::STATUS_BROUILLON])
//             ->where('created_at', '>=', now()->subDays($days))
//             ->groupBy('date')
//             ->orderBy('date')
//             ->get();


//         // Remplir les jours manquants avec 0
//         $data   = [];
//         $period = now()->subDays($days);

//         while ($period->lte(now())) {
//             $dateStr = $period->toDateString();
//             $found   = $results->firstWhere('date', $dateStr);

//             $data[] = [
//                 'date'    => $dateStr,
//                 'nombre'  => $found ? (int) $found->nombre : 0,
//                 'montant' => $found ? (float) $found->montant : 0,
//             ];

//             $period->addDay();
//         }

//         return $data;
//     }

//     // ─── Ventes par mois (N derniers mois) ────────────
//     private function ventesParMois(int $months = 12): array
//     {
//         $currentOrgId = session('current_organization_id');

//         $results = DB::table('sales')
//             ->selectRaw('YEAR(created_at) as annee, MONTH(created_at) as mois, COUNT(*) as nombre, SUM(total_amount) as montant')
//             ->where('organization_id', $currentOrgId)
//             ->whereNotIn('status', [Sale::STATUS_ANNULEE, Sale::STATUS_BROUILLON])
//             ->where('created_at', '>=', now()->subMonths($months))
//             ->groupBy('annee', 'mois')
//             ->orderBy('annee')
//             ->orderBy('mois')
//             ->get();


//         $data   = [];
//         $period = now()->subMonths($months)->startOfMonth();

//         while ($period->lte(now())) {
//             $found = $results->first(fn($r) =>
//                 $r->annee == $period->year && $r->mois == $period->month
//             );

//             $data[] = [
//                 'mois'    => $period->format('M Y'),
//                 'nombre'  => $found ? (int) $found->nombre : 0,
//                 'montant' => $found ? (float) $found->montant : 0,
//             ];

//             $period->addMonth();
//         }

//         return $data;
//     }

//     // ─── GET /api/v1/dashboard/charts/categories ──────
//     public function chartsCategories(): JsonResponse
//     {
//         $thisMonth = now()->month;
//         $thisYear  = now()->year;

//         $data = DB::table('sale_items')
//             ->join('products',   'sale_items.product_id',  '=', 'products.id')
//             ->join('categories', 'products.category_id',   '=', 'categories.id')
//             ->join('sales',      'sale_items.sale_id',     '=', 'sales.id')
//             ->whereMonth('sales.created_at', $thisMonth)
//             ->whereYear('sales.created_at', $thisYear)
//             ->whereNotIn('sales.status', [Sale::STATUS_ANNULEE, Sale::STATUS_BROUILLON])
//             ->whereNull('products.deleted_at')
//             ->select(
//                 'categories.id',
//                 'categories.name',
//                 DB::raw('SUM(sale_items.quantity) as total_quantite'),
//                 DB::raw('SUM(sale_items.total_price) as total_montant'),
//                 DB::raw('COUNT(DISTINCT sale_items.sale_id) as nombre_ventes')
//             )
//             ->groupBy('categories.id', 'categories.name')
//             ->orderByDesc('total_montant')
//             ->get();

//         return response()->json([
//             'success' => true,
//             'data'    => $data,
//         ], 200);
//     }

//     // ─── GET /api/v1/dashboard/alerts ─────────────────
//     public function alerts(): JsonResponse
//     {
//         // Produits en alerte stock
//         $stockAlertes = Product::with('category')
//             ->lowStock()
//             ->whereNull('deleted_at')
//             ->orderBy('stock_quantity', 'asc')
//             ->get()
//             ->map(fn($p) => [
//                 'id'             => $p->id,
//                 'name'           => $p->name,
//                 'reference'      => $p->reference,
//                 'stock_quantity' => $p->stock_quantity,
//                 'stock_alert'    => $p->stock_alert,
//                 'category'       => $p->category?->name,
//                 'is_rupture'     => $p->isOutOfStock(),
//             ]);

//         // Commandes en attente
//         $commandesAttente = Sale::with('client')
//             ->whereIn('status', [
//                 Sale::STATUS_CONFIRMEE,
//                 Sale::STATUS_EN_PREPARATION,
//             ])
//             ->orderBy('created_at', 'asc')
//             ->get()
//             ->map(fn($s) => [
//                 'id'          => $s->id,
//                 'sale_number' => $s->sale_number,
//                 'status'      => $s->status,
//                 'status_libelle' => $s->status_libelle,
//                 'client'      => $s->client?->full_name ?? 'Client anonyme',
//                 'total'       => (float) $s->total_amount,
//                 'created_at'  => $s->created_at?->format('d/m/Y H:i'),
//             ]);

//         return response()->json([
//             'success' => true,
//             'data'    => [
//                 'stock_alertes'     => $stockAlertes,
//                 'commandes_attente' => $commandesAttente,
//                 'totaux'            => [
//                     'stock_alertes'     => $stockAlertes->count(),
//                     'commandes_attente' => $commandesAttente->count(),
//                 ],
//             ],
//         ], 200);
//     }
// }


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // ─── GET /api/v1/dashboard/kpis ───────────────────
    public function kpis(): JsonResponse
    {
        $today     = now()->toDateString();
        $thisMonth = now()->month;
        $thisYear  = now()->year;
        $user      = auth()->user();

        $isGlobalSA = $user->isSuperAdmin() && !session('current_organization_id');

        if ($isGlobalSA) {
            $caJour  = DB::table('subscriptions')->whereDate('created_at', $today)->where('status', 'paid')->sum('amount');
            $caMois  = DB::table('subscriptions')->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->where('status', 'paid')->sum('amount');
            $caAnnee = DB::table('subscriptions')->whereYear('created_at', $thisYear)->where('status', 'paid')->sum('amount');

            $ventesJour = DB::table('organizations')->whereDate('created_at', $today)->count();
            $ventesMois = DB::table('organizations')->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count();

            $valeurStock      = DB::table('organizations')->count();
            $produitsEnAlerte = DB::table('organizations')->where('is_active', true)->count();
            $produitsRupture  = DB::table('users')->where('is_active', true)->count();

            $commandesEnAttente = DB::table('subscriptions')->where('status', 'pending')->count();
            $commandesBrouillon = 0;

            $topProduits = DB::table('organizations')
                ->leftJoin('subscriptions', 'organizations.id', '=', 'subscriptions.organization_id')
                ->select('organizations.id', 'organizations.name', DB::raw('SUM(subscriptions.amount) as total_ca'))
                ->where('subscriptions.status', 'paid')
                ->groupBy('organizations.id', 'organizations.name')
                ->orderByDesc('total_ca')
                ->limit(5)
                ->get();
        } else {
            $salesBase = Sale::forCurrentOrganization();

            $caJour = (clone $salesBase)
                ->whereDate('created_at', $today)
                ->whereNotIn('status', [Sale::STATUS_ANNULEE])
                ->sum('total_amount');

            $caMois = (clone $salesBase)
                ->whereMonth('created_at', $thisMonth)
                ->whereYear('created_at', $thisYear)
                ->whereNotIn('status', [Sale::STATUS_ANNULEE])
                ->sum('total_amount');

            $caAnnee = (clone $salesBase)
                ->whereYear('created_at', $thisYear)
                ->whereNotIn('status', [Sale::STATUS_ANNULEE])
                ->sum('total_amount');

            $ventesJour = (clone $salesBase)
                ->whereDate('created_at', $today)
                ->whereNotIn('status', [Sale::STATUS_ANNULEE])
                ->count();

            $ventesMois = (clone $salesBase)
                ->whereMonth('created_at', $thisMonth)
                ->whereYear('created_at', $thisYear)
                ->whereNotIn('status', [Sale::STATUS_ANNULEE])
                ->count();

            $productsBase = Product::forCurrentOrganization();

            $valeurStock = (clone $productsBase)
                ->selectRaw('SUM(stock_quantity * purchase_price) as valeur')
                ->whereNull('deleted_at')
                ->value('valeur') ?? 0;

            $produitsEnAlerte = (clone $productsBase)
                ->lowStock()
                ->whereNull('deleted_at')
                ->count();

            $produitsRupture = (clone $productsBase)
                ->where('stock_quantity', 0)
                ->whereNull('deleted_at')
                ->count();

            $commandesEnAttente = (clone $salesBase)
                ->where('status', Sale::STATUS_CONFIRMEE)
                ->count();

            $commandesBrouillon = (clone $salesBase)
                ->where('status', Sale::STATUS_ENCOURS)
                ->count();

            $currentOrgId = session('current_organization_id');

            $topProduits = DB::table('sale_items')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.organization_id', $currentOrgId)
                ->whereMonth('sales.created_at', $thisMonth)
                ->whereYear('sales.created_at', $thisYear)
                ->whereNotIn('sales.status', [Sale::STATUS_ANNULEE])
                ->where('products.organization_id', $currentOrgId)
                ->whereNull('products.deleted_at')
                ->select(
                    'products.id',
                    'products.name',
                    'products.reference',
                    DB::raw('SUM(sale_items.quantity) as total_vendu'),
                    DB::raw('SUM(sale_items.total_price) as total_ca')
                )
                ->groupBy('products.id', 'products.name', 'products.reference')
                ->orderByDesc('total_vendu')
                ->limit(5)
                ->get();
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'chiffre_affaires' => [
                    'jour'  => (float) $caJour,
                    'mois'  => (float) $caMois,
                    'annee' => (float) $caAnnee,
                ],
                'ventes' => [
                    'jour' => $ventesJour,
                    'mois' => $ventesMois,
                ],
                'stock' => [
                    'valeur_totale'      => (float) $valeurStock,
                    'produits_en_alerte' => $produitsEnAlerte,
                    'produits_rupture'   => $produitsRupture,
                ],
                'commandes' => [
                    'en_attente' => $commandesEnAttente,
                    'brouillon'  => $commandesBrouillon,
                ],
                'top_produits' => $topProduits,
            ],
        ], 200);
    }

    // ─── GET /api/v1/dashboard/charts/sales ───────────
    public function chartsSales(): JsonResponse
    {
        $period = request('period', 'month');

        $data = match($period) {
            'day'  => $this->ventesParJour(),
            'year' => $this->ventesParMois(12),
            default => $this->ventesParJour(30),
        };

        return response()->json([
            'success' => true,
            'period'  => $period,
            'data'    => $data,
        ], 200);
    }

    // ─── Ventes par jour ──────────────────────────────
    private function ventesParJour(int $days = 30): array
    {
        $currentOrgId = session('current_organization_id');

        $results = DB::table('sales')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as nombre, SUM(total_amount) as montant')
            ->where('organization_id', $currentOrgId)
            ->whereNotIn('status', [Sale::STATUS_ANNULEE])
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $data   = [];
        $period = now()->subDays($days);

        while ($period->lte(now())) {
            $dateStr = $period->toDateString();
            $found   = $results->firstWhere('date', $dateStr);

            $data[] = [
                'date'    => $dateStr,
                'nombre'  => $found ? (int) $found->nombre : 0,
                'montant' => $found ? (float) $found->montant : 0,
            ];

            $period->addDay();
        }

        return $data;
    }

    // ─── Ventes par mois ──────────────────────────────
    private function ventesParMois(int $months = 12): array
    {
        $currentOrgId = session('current_organization_id');

        $results = DB::table('sales')
            ->selectRaw('YEAR(created_at) as annee, MONTH(created_at) as mois, COUNT(*) as nombre, SUM(total_amount) as montant')
            ->where('organization_id', $currentOrgId)
            ->whereNotIn('status', [Sale::STATUS_ANNULEE])
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('annee', 'mois')
            ->orderBy('annee')
            ->orderBy('mois')
            ->get();

        $data   = [];
        $period = now()->subMonths($months)->startOfMonth();

        while ($period->lte(now())) {
            $found = $results->first(fn($r) =>
                $r->annee == $period->year && $r->mois == $period->month
            );

            $data[] = [
                'mois'    => $period->format('M Y'),
                'nombre'  => $found ? (int) $found->nombre : 0,
                'montant' => $found ? (float) $found->montant : 0,
            ];

            $period->addMonth();
        }

        return $data;
    }

    // ─── GET /api/v1/dashboard/charts/categories ──────
    public function chartsCategories(): JsonResponse
    {
        $thisMonth = now()->month;
        $thisYear  = now()->year;

        $data = DB::table('sale_items')
            ->join('products',   'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id',  '=', 'categories.id')
            ->join('sales',      'sale_items.sale_id',    '=', 'sales.id')
            ->whereMonth('sales.created_at', $thisMonth)
            ->whereYear('sales.created_at', $thisYear)
            ->whereNotIn('sales.status', [Sale::STATUS_ANNULEE])
            ->whereNull('products.deleted_at')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('SUM(sale_items.quantity) as total_quantite'),
                DB::raw('SUM(sale_items.total_price) as total_montant'),
                DB::raw('COUNT(DISTINCT sale_items.sale_id) as nombre_ventes')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_montant')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ], 200);
    }

    // ─── GET /api/v1/dashboard/alerts ─────────────────
    public function alerts(): JsonResponse
    {
        $stockAlertes = Product::with('category')
            ->lowStock()
            ->whereNull('deleted_at')
            ->orderBy('stock_quantity', 'asc')
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'name'           => $p->name,
                'reference'      => $p->reference,
                'stock_quantity' => $p->stock_quantity,
                'stock_alert'    => $p->stock_alert,
                'category'       => $p->category?->name,
                'is_rupture'     => $p->isOutOfStock(),
            ]);

        $commandesAttente = Sale::with('client')
            ->where('status', Sale::STATUS_CONFIRMEE)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($s) => [
                'id'             => $s->id,
                'sale_number'    => $s->sale_number,
                'status'         => $s->status,
                'status_libelle' => $s->status_libelle,
                'client'         => $s->client?->full_name ?? 'Client anonyme',
                'total'          => (float) $s->total_amount,
                'created_at'     => $s->created_at?->format('d/m/Y H:i'),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'stock_alertes'     => $stockAlertes,
                'commandes_attente' => $commandesAttente,
                'totaux'            => [
                    'stock_alertes'     => $stockAlertes->count(),
                    'commandes_attente' => $commandesAttente->count(),
                ],
            ],
        ], 200);
    }
}