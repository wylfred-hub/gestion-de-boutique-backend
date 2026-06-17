<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $machinesAuto  = Category::where('slug', 'machines-automatiques')->first();
        $machinesManuel = Category::where('slug', 'machines-manuelles')->first();
        $tampons       = Category::where('slug', 'tampons-encreurs')->first();
        $plaques       = Category::where('slug', 'plaques')->first();
        $encres        = Category::where('slug', 'encres')->first();
        $recharges     = Category::where('slug', 'recharges')->first();

        // ─── Machines automatiques ────────────────────
        Product::create([
            'category_id'    => $machinesAuto->id,
            'name'           => 'Machine à cachet automatique Pro',
            'description'    => 'Machine professionnelle pour cachets automatiques',
            'purchase_price' => 45000,
            'selling_price'  => 65000,
            'unit'           => 'pièce',
            'stock_quantity' => 10,
            'stock_alert'    => 3,
            'is_active'      => true,
        ]);

        Product::create([
            'category_id'    => $machinesAuto->id,
            'name'           => 'Machine à cachet automatique Standard',
            'description'    => 'Machine standard pour cachets automatiques',
            'purchase_price' => 25000,
            'selling_price'  => 38000,
            'unit'           => 'pièce',
            'stock_quantity' => 15,
            'stock_alert'    => 5,
            'is_active'      => true,
        ]);

        // ─── Machines manuelles ───────────────────────
        Product::create([
            'category_id'    => $machinesManuel->id,
            'name'           => 'Machine à cachet manuelle Classic',
            'description'    => 'Machine manuelle classique pour cachets',
            'purchase_price' => 8000,
            'selling_price'  => 12000,
            'unit'           => 'pièce',
            'stock_quantity' => 20,
            'stock_alert'    => 5,
            'is_active'      => true,
        ]);

        // ─── Tampons encreurs ─────────────────────────
        Product::create([
            'category_id'    => $tampons->id,
            'name'           => 'Tampon encreur rouge',
            'description'    => 'Tampon encreur de couleur rouge',
            'purchase_price' => 1500,
            'selling_price'  => 2500,
            'unit'           => 'pièce',
            'stock_quantity' => 50,
            'stock_alert'    => 10,
            'is_active'      => true,
        ]);

        Product::create([
            'category_id'    => $tampons->id,
            'name'           => 'Tampon encreur bleu',
            'description'    => 'Tampon encreur de couleur bleue',
            'purchase_price' => 1500,
            'selling_price'  => 2500,
            'unit'           => 'pièce',
            'stock_quantity' => 2,  // ← en alerte
            'stock_alert'    => 10,
            'is_active'      => true,
        ]);

        // ─── Plaques ──────────────────────────────────
        Product::create([
            'category_id'    => $plaques->id,
            'name'           => 'Plaque standard 40x40mm',
            'description'    => 'Plaque pour cachet format 40x40mm',
            'purchase_price' => 3000,
            'selling_price'  => 5000,
            'unit'           => 'pièce',
            'stock_quantity' => 30,
            'stock_alert'    => 8,
            'is_active'      => true,
        ]);

        // ─── Encres ───────────────────────────────────
        Product::create([
            'category_id'    => $encres->id,
            'name'           => 'Encre noire 30ml',
            'description'    => 'Flacon d\'encre noire 30ml',
            'purchase_price' => 800,
            'selling_price'  => 1500,
            'unit'           => 'flacon',
            'stock_quantity' => 3,  // ← en alerte
            'stock_alert'    => 5,
            'is_active'      => true,
        ]);

        // ─── Recharges ────────────────────────────────
        Product::create([
            'category_id'    => $recharges->id,
            'name'           => 'Recharge universelle Pro',
            'description'    => 'Recharge universelle pour machines Pro',
            'purchase_price' => 5000,
            'selling_price'  => 8000,
            'unit'           => 'pièce',
            'stock_quantity' => 25,
            'stock_alert'    => 5,
            'is_active'      => true,
        ]);
    }
}