<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            // ─── Clients particuliers ─────────────────────
            [
                'type'       => Client::TYPE_PARTICULIER,
                'first_name' => 'Jean',
                'last_name'  => 'Dupont',
                'email'      => 'jean.dupont@email.com',
                'phone'      => '+237 699 000 001',
                'address'    => 'Douala, Akwa',
                'category'   => Client::CAT_STANDARD,
            ],
            [
                'type'       => Client::TYPE_PARTICULIER,
                'first_name' => 'Marie',
                'last_name'  => 'Nguema',
                'email'      => 'marie.nguema@email.com',
                'phone'      => '+237 677 000 002',
                'address'    => 'Douala, Bonapriso',
                'category'   => Client::CAT_VIP,
                'notes'      => 'Cliente fidèle depuis 2023',
            ],
            [
                'type'       => Client::TYPE_PARTICULIER,
                'first_name' => 'Paul',
                'last_name'  => 'Mbarga',
                'email'      => 'paul.mbarga@email.com',
                'phone'      => '+237 655 000 003',
                'address'    => 'Yaoundé, Bastos',
                'category'   => Client::CAT_STANDARD,
            ],

            // ─── Clients entreprises ──────────────────────
            [
                'type'         => Client::TYPE_ENTREPRISE,
                'company_name' => 'Imprimerie Centrale',
                'email'        => 'contact@imprimerie-centrale.cm',
                'phone'        => '+237 233 000 001',
                'address'      => 'Douala, Zone Industrielle',
                'category'     => Client::CAT_VIP,
                'notes'        => 'Commandes groupées chaque trimestre',
            ],
            [
                'type'         => Client::TYPE_ENTREPRISE,
                'company_name' => 'Bureau Services SARL',
                'email'        => 'info@bureau-services.cm',
                'phone'        => '+237 233 000 002',
                'address'      => 'Yaoundé, Centre Administratif',
                'category'     => Client::CAT_STANDARD,
            ],
            [
                'type'         => Client::TYPE_ENTREPRISE,
                'company_name' => 'Mairie de Douala',
                'email'        => 'commandes@mairie-douala.cm',
                'phone'        => '+237 233 000 003',
                'address'      => 'Douala, Bonanjo',
                'category'     => Client::CAT_VIP,
                'notes'        => 'Client institutionnel - bon de commande obligatoire',
            ],
        ];

        foreach ($clients as $client) {
            Client::updateOrCreate(
                ['email' => $client['email']],
                $client
            );
        }

        echo "✓ Clients prêts (idempotent)\n";
    }
}

