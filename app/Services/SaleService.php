<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SaleService
{
    public function __construct(
        private StockService $stockService
    ) {}

    // ─── Générer numéro de vente unique ───────────────
    private function genererNumeroVente(int $orgId): string
    {
        $year   = date('Y');
        $prefix = 'VTE-' . $year . '-';

        $last = Sale::where('organization_id', $orgId)
            ->whereYear('created_at', $year)
            ->where('sale_number', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(sale_number, ' . (strlen($prefix) + 1) . ') AS INTEGER) DESC')
            ->value('sale_number');

        $next = $last
            ? (int) substr($last, strlen($prefix)) + 1
            : 1;

        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    // ─── Calculer total d'un item ─────────────────────
    private function calculerTotalItem(
        int     $quantity,
        float   $unitPrice,
        ?string $discountType  = null,
        float   $discountValue = 0
    ): float {
        $subtotal = $quantity * $unitPrice;

        if (!$discountType || $discountValue <= 0) {
            return $subtotal;
        }

        if ($discountType === Sale::DISCOUNT_POURCENTAGE) {
            return $subtotal - ($subtotal * $discountValue / 100);
        }

        return max(0, $subtotal - $discountValue);
    }

    // ─── Calculer total de la vente ───────────────────
    private function calculerTotalVente(
        float   $subtotal,
        ?string $discountType  = null,
        float   $discountValue = 0
    ): float {
        if (!$discountType || $discountValue <= 0) {
            return $subtotal;
        }

        if ($discountType === Sale::DISCOUNT_POURCENTAGE) {
            return $subtotal - ($subtotal * $discountValue / 100);
        }

        return max(0, $subtotal - $discountValue);
    }

    // ─── Créer une vente ──────────────────────────────
    public function creerVente(array $data): Sale
    {
        return DB::transaction(function () use ($data) {

            // 1. Vérifier disponibilité stock
            $erreurs = $this->stockService->verifierDisponibilites($data['items']);
            if (!empty($erreurs)) {
                throw new Exception(implode(' | ', $erreurs));
            }

            // 2. Calculer les totaux
            $subtotal  = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $totalItem = $this->calculerTotalItem(
                    $item['quantity'],
                    $item['unit_price'],
                    $item['discount_type']  ?? null,
                    $item['discount_value'] ?? 0
                );

                $subtotal += $totalItem;

                $itemsData[] = [
                    'product_id'     => $item['product_id'],
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $item['unit_price'],
                    'discount_type'  => $item['discount_type']  ?? null,
                    'discount_value' => $item['discount_value'] ?? 0,
                    'total_price'    => $totalItem,
                ];
            }

            $totalAmount = $this->calculerTotalVente(
                $subtotal,
                $data['discount_type']  ?? null,
                $data['discount_value'] ?? 0
            );

            // 3. Créer la vente
            $sale = Sale::create([
                'organization_id' => $data['organization_id'], // ← AJOUT
                'client_id'       => $data['client_id']       ?? null,
                'user_id'         => auth()->id(),
                'sale_number' => $this->genererNumeroVente((int) $data['organization_id']),
                'status'          => 'encours',
                'discount_type'   => $data['discount_type']   ?? null,
                'discount_value'  => $data['discount_value']  ?? 0,
                'subtotal'        => $subtotal,
                'total_amount'    => $totalAmount,
                'notes'           => $data['notes']           ?? null,
            ]);

            // 4. Créer les items
            foreach ($itemsData as $itemData) {
                $sale->items()->create($itemData);
            }

            // 5. Décrémenter le stock
            foreach ($itemsData as $itemData) {
                $product = Product::find($itemData['product_id']);
                $this->stockService->sortie(
                    $product,
                    $itemData['quantity'],
                    'Vente ' . $sale->sale_number,
                    $sale->id,
                    'sale'
                );
            }

            return $sale->load(['client', 'user', 'items.product']);
        });
    }

    // ─── Modifier une vente (brouillon seulement) ─────
    public function modifierVente(Sale $sale, array $data): Sale
    {
        Log::info('modifierVente status: ' . $sale->status);
        return DB::transaction(function () use ($sale, $data) {

            if ($sale->status !== 'encours') {
                throw new Exception('Seules les ventes encours peuvent être modifiées.');
            }

            $updateData = [];

            if (array_key_exists('client_id', $data)) {
                $updateData['client_id'] = $data['client_id'];
            }

            if (array_key_exists('discount_type', $data)) {
                $updateData['discount_type'] = $data['discount_type'];
            }

            if (array_key_exists('discount_value', $data)) {
                $updateData['discount_value'] = $data['discount_value'] ?? 0;
            }

            if (array_key_exists('notes', $data)) {
                $updateData['notes'] = $data['notes'];
            }

            if (!array_key_exists('items', $data)) {
                if (array_key_exists('discount_type', $data) || array_key_exists('discount_value', $data)) {
                    $updateData['subtotal'] = $sale->subtotal;
                    $updateData['total_amount'] = $this->calculerTotalVente(
                        $updateData['subtotal'],
                        $updateData['discount_type'] ?? $sale->discount_type,
                        array_key_exists('discount_value', $data)
                            ? $updateData['discount_value']
                            : $sale->discount_value
                    );
                }

                if (!empty($updateData)) {
                    $sale->update($updateData);
                }

                return $sale->fresh()->load(['client', 'user', 'items.product']);
            }

            // Réintégrer stock anciens items
            foreach ($sale->items as $item) {
                $product = Product::find($item->product_id);
                $this->stockService->retour(
                    $product,
                    $item->quantity,
                    'Modification vente ' . $sale->sale_number,
                    $sale->id,
                    'sale'
                );
            }

            $sale->items()->delete();

            // Vérifier nouveau stock
            $erreurs = $this->stockService->verifierDisponibilites($data['items']);
            if (!empty($erreurs)) {
                throw new Exception(implode(' | ', $erreurs));
            }

            // Recalculer
            $subtotal  = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $totalItem = $this->calculerTotalItem(
                    $item['quantity'], 
                    $item['unit_price'], 
                    $item['discount_type']  ?? null,
                    $item['discount_value'] ?? 0
                );

                $subtotal   += $totalItem;
                $itemsData[] = [
                    'product_id'     => $item['product_id'],
                    'quantity'       => $item['quantity'],
                    'unit_price'     => $item['unit_price'],
                    'discount_type'  => $item['discount_type']  ?? null,
                    'discount_value' => $item['discount_value'] ?? 0,
                    'total_price'    => $totalItem,
                ];
            }

            $totalAmount = $this->calculerTotalVente(
                $subtotal,
                $data['discount_type']  ?? null,
                $data['discount_value'] ?? 0
            );

            $sale->update(array_merge($updateData, [
                'subtotal'      => $subtotal,
                'total_amount'  => $totalAmount,
            ]));

            foreach ($itemsData as $itemData) {
                $sale->items()->create($itemData);
            }

            foreach ($itemsData as $itemData) {
                $product = Product::find($itemData['product_id']);
                $this->stockService->sortie(
                    $product,
                    $itemData['quantity'], 
                    'Vente ' . $sale->sale_number,
                    $sale->id,
                    'sale'
                );
            }

            return $sale->fresh()->load(['client', 'user', 'items.product']);
        });
    }

    // ─── Changer le statut ────────────────────────────
    public function changerStatut(Sale $sale, string $newStatus, ?string $notes = null): Sale
    {
        return DB::transaction(function () use ($sale, $newStatus, $notes) {

            if (!$sale->canTransitionTo($newStatus)) {
                throw new Exception(
                    "Transition impossible de '{$sale->status}' vers '{$newStatus}'."
                );
            }

            $updateData = ['status' => $newStatus];

            // Annulation (annulee) → réintégrer stock
            if ($newStatus === 'annulee') {
                foreach ($sale->items as $item) {
                    $product = Product::find($item->product_id);
                    $this->stockService->retour(
                        $product,
                        $item->quantity,
                        'Annulation vente ' . $sale->sale_number,
                        $sale->id,
                        'sale'
                    );
                }
            }

            // Confirmation (confirmee) → enregistrer la date (optionnel, selon tes besoins)
            if ($newStatus === 'confirmee') {
                $updateData['delivered_at'] = now();
            }

            if ($notes) {
                $updateData['notes'] = $sale->notes
                    ? $sale->notes . "\n" . $notes
                    : $notes;
            }

            $sale->update($updateData);

            return $sale->fresh()->load(['client', 'user', 'items.product']);
        });
    }

    // ─── Retour de produits ───────────────────────────
    public function retourVente(Sale $sale, array $items, string $reason): array
    {
        return DB::transaction(function () use ($sale, $items, $reason) {

            if ($sale->status !== 'confirmee') {
                throw new Exception('Les retours ne sont possibles que pour les ventes livrées.');
            }

            $retours = [];

            foreach ($items as $itemData) {
                $saleItem = SaleItem::find($itemData['sale_item_id']);

                if (!$saleItem || $saleItem->sale_id !== $sale->id) {
                    throw new Exception('Article introuvable dans cette vente.');
                }

                if ($itemData['quantity'] > $saleItem->quantity) {
                    throw new Exception(
                        "La quantité retournée ({$itemData['quantity']}) "
                            . "dépasse la quantité vendue ({$saleItem->quantity})."
                    );
                }

                $product  = Product::find($saleItem->product_id);
                $movement = $this->stockService->retour(
                    $product,
                    $itemData['quantity'],
                    $reason . ' - Vente ' . $sale->sale_number,
                    $sale->id,
                    'sale'
                );

                $retours[] = [
                    'product'  => $product->name,
                    'quantity' => $itemData['quantity'],
                    'movement' => $movement->id,
                ];
            }

            return $retours;
        });
    }
}
