<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{
    // ─── Méthode centrale ─────────────────────────────
    private function enregistrerMouvement(
        Product $product,
        string  $type,
        int     $quantity,
        ?string $reason       = null,
        ?int    $referenceId  = null,
        ?string $referenceType = null
    ): StockMovement {
        $quantityBefore = (int) $product->getAttribute('stock_quantity');
        $quantityAfter  = match($type) {
            StockMovement::TYPE_ENTREE     => $quantityBefore + $quantity,
            StockMovement::TYPE_SORTIE     => $quantityBefore - $quantity,
            StockMovement::TYPE_RETOUR     => $quantityBefore + $quantity,
            StockMovement::TYPE_AJUSTEMENT => $quantity, // quantity = nouvelle valeur
        };

        // Vérifier que le stock ne devient pas négatif
        if ($quantityAfter < 0) {
            throw new Exception(
                "Stock insuffisant. Stock actuel : {$quantityBefore}, quantité demandée : {$quantity}."
            );
        }

        // Mettre à jour le stock du produit
        $product->update(['stock_quantity' => $quantityAfter]);

        // Enregistrer le mouvement
        // @phpstan-ignore-next-line
        return StockMovement::create([
            'organization_id'  => $product->organization_id,
            'product_id'       => $product->id,
            'user_id'          => auth()->id(),
            'type'             => $type,
            'quantity'         => $quantity,
            'quantity_before'  => $quantityBefore,
            'quantity_after'   => $quantityAfter,
            'reference_id'     => $referenceId,
            'reference_type'   => $referenceType,
        ]);
    }

    // ─── Entrée de stock (réapprovisionnement) ────────
    public function entree(
        Product $product,
        int     $quantity,
        ?string $reason = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $quantity, $reason) {
            return $this->enregistrerMouvement(
                $product,
                StockMovement::TYPE_ENTREE,
                $quantity,
                null,
                null,
                null
            );
        });
    }

    // ─── Sortie de stock (vente) ──────────────────────
    public function sortie(
        Product $product,
        int     $quantity,
        ?string $reason      = null,
        ?int    $referenceId = null,
        ?string $referenceType = null
    ): StockMovement {
        return DB::transaction(function () use (
            $product, $quantity, $reason, $referenceId, $referenceType
        ) {
            return $this->enregistrerMouvement(
                $product,
                StockMovement::TYPE_SORTIE,
                $quantity,
                null,
                $referenceId,
                $referenceType
            );
        });
    }

    // ─── Ajustement de stock (inventaire) ────────────
    public function ajustement(
        Product $product,
        int     $newQuantity,
        string  $reason
    ): StockMovement {
        return DB::transaction(function () use ($product, $newQuantity, $reason) {
            return $this->enregistrerMouvement(
                $product,
                StockMovement::TYPE_AJUSTEMENT,
                $newQuantity,
                null,
                null,
                null
            );
        });
    }

    // ─── Perte / Casse ────────────────────────────────
    public function perte(
        Product $product,
        int     $quantity,
        string  $reason
    ): StockMovement {
        return DB::transaction(function () use ($product, $quantity, $reason) {
            return $this->enregistrerMouvement(
                $product,
                StockMovement::TYPE_SORTIE,
                $quantity,
                null,
                null,
                null
            );
        });
    }

    // ─── Retour produit (annulation vente) ────────────
    public function retour(
        Product $product,
        int     $quantity,
        ?string $reason      = null,
        ?int    $referenceId = null,
        ?string $referenceType = null
    ): StockMovement {
        return DB::transaction(function () use (
            $product, $quantity, $reason, $referenceId, $referenceType
        ) {
            return $this->enregistrerMouvement(
                $product,
                StockMovement::TYPE_RETOUR,
                $quantity,
                null,
                $referenceId,
                $referenceType
            );
        });
    }

    // ─── Vérifier disponibilité stock ─────────────────
    public function verifierDisponibilite(Product $product, int $quantity): bool
    {
        return $product->stock_quantity >= $quantity;
    }

    // ─── Vérifier disponibilité pour plusieurs produits
    public function verifierDisponibilites(array $items): array
    {
        $erreurs = [];

        foreach ($items as $item) {
            $product = Product::forCurrentOrganization()->find($item['product_id']);

            if (!$product) {
                $erreurs[] = "Produit ID {$item['product_id']} introuvable.";
                continue;
            }

            if (!$this->verifierDisponibilite($product, $item['quantity'])) {
                $erreurs[] = "Stock insuffisant pour {$product->name}. "
                    . "Stock disponible : {$product->stock_quantity}, "
                    . "quantité demandée : {$item['quantity']}.";
            }
        }

        return $erreurs;
    }
}