<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte unique globale
            $table->dropUnique('sales_sale_number_unique');
            
            // Ajouter une contrainte unique par organisation
            $table->unique(['organization_id', 'sale_number'], 'sales_org_sale_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_org_sale_number_unique');
            $table->unique('sale_number', 'sales_sale_number_unique');
        });
    }
};