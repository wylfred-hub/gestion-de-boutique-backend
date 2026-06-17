<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // OrganizationSeeder::class,  // Créer les organisations en premier
            UserSeeder::class,          // Puis les utilisateurs liés aux organisations
            // CategorySeeder::class,
            // ProductSeeder::class,
            // ClientSeeder::class,
        ]);
    }
}