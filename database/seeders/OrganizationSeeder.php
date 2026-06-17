<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer quelques organisations de test (idempotent grâce au slug unique)
        $organizations = [
            [
                'name' => 'Acme Corporation',
                'slug' => 'acme-corporation',
                'email' => 'contact@acme.local',
                'phone' => '+212 5 22 12 34 56',
                'address' => '123 Rue de la Paix',
                'city' => 'Casablanca',
                'postal_code' => '20000',
                'country' => 'Maroc',
                'description' => 'Notre entreprise principale',
                'is_active' => true,
            ],
            [
                'name' => 'Tech Solutions',
                'slug' => 'tech-solutions',
                'email' => 'info@techsol.local',
                'phone' => '+212 5 22 98 76 54',
                'address' => '456 Avenue du Commerce',
                'city' => 'Rabat',
                'postal_code' => '10000',
                'country' => 'Maroc',
                'description' => 'Filiale technologique',
                'is_active' => true,
            ],
            [
                'name' => 'Global Trade Ltd',
                'slug' => 'global-trade-ltd',
                'email' => 'sales@globaltrade.local',
                'phone' => '+212 5 22 55 44 33',
                'address' => '789 Boulevard International',
                'city' => 'Marrakech',
                'postal_code' => '40000',
                'country' => 'Maroc',
                'description' => 'Division internationale',
                'is_active' => true,
            ],
        ];

        foreach ($organizations as $org) {
            Organization::updateOrCreate(
                ['slug' => $org['slug']],
                $org
            );
        }

        echo "✓ Organisations prêtes (idempotent)\n";
    }
}

