<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Suppression de l'unicité globale sur reference (genère les collisions entre organisations)

            $table->dropUnique('products_reference_unique');

            // Ajout de l'unicité composite par organisation
            $table->unique(['organization_id', 'reference'], 'products_organization_reference_unique');
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

