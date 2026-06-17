<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Helper simple pour rendre le seeding idempotent (slug unique)
        $upsertCategory = function (array $data) {
            return Category::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        };

        // ─── Catégories principales ───────────────────
        $appareils = $upsertCategory([
            'name'        => 'Appareils'
        ]);

        $accessoires = $upsertCategory([
            'name'        => 'Accessoires',
            'slug'        => 'accessoires',
            'description' => 'Accessoires pour appareils à cachets',
        ]);

        $consommables = $upsertCategory([
            'name'        => 'Consommables',
            'slug'        => 'consommables',
            'description' => 'Produits consommables pour cachets',
        ]);

        // ─── Sous-catégories Appareils ────────────────
        $upsertCategory([
            'name'        => 'Machines automatiques',
            'slug'        => 'machines-automatiques',
            'parent_id'   => $appareils->id,
            'description' => 'Machines à cachets automatiques',
        ]);

        $upsertCategory([
            'name'        => 'Machines manuelles',
            'slug'        => 'machines-manuelles',
            'parent_id'   => $appareils->id,
            'description' => 'Machines à cachets manuelles',
        ]);

        // ─── Sous-catégories Accessoires ──────────────
        $upsertCategory([
            'name'        => 'Tampons encreurs',
            'slug'        => 'tampons-encreurs',
            'parent_id'   => $accessoires->id,
            'description' => 'Tampons encreurs pour cachets',
        ]);

        $upsertCategory([
            'name'        => 'Plaques',
            'slug'        => 'plaques',
            'parent_id'   => $accessoires->id,
            'description' => 'Plaques pour cachets',
        ]);

        // ─── Sous-catégories Consommables ─────────────
        $upsertCategory([
            'name'        => 'Encres',
            'slug'        => 'encres',
            'parent_id'   => $consommables->id,
            'description' => 'Encres pour cachets',
        ]);

        $upsertCategory([
            'name'        => 'Recharges',
            'slug'        => 'recharges',
            'parent_id'   => $consommables->id,
            'description' => 'Recharges pour appareils à cachets',
        ]);
    }
}

