<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Suppression de l'unicité globale sur reference (genère les collisions entre organisations)
            // Note: le nom de contrainte peut ne pas exister selon l'état réel de la DB.
            // On supprime via SQL 'IF EXISTS' pour éviter un échec de migration.
            DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_reference_unique');

            // Ajout de l'unicité composite par organisation
            DB::statement('ALTER TABLE products ADD CONSTRAINT products_organization_reference_unique UNIQUE (organization_id, reference)');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Retirer l'unique composite
            $table->dropUnique('products_organization_reference_unique');

            // Remettre l'unique globale sur reference
            $table->unique('reference', 'products_reference_unique');
        });
    }
};

