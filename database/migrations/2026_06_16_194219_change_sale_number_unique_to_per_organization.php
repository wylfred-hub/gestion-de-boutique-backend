<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Supprimer l'ancienne contrainte unique globale (peut ne pas exister selon l'état réel de la DB)
            DB::statement('ALTER TABLE sales DROP CONSTRAINT IF EXISTS sales_sale_number_unique');

            // Ajouter une contrainte unique par organisation
            DB::statement('ALTER TABLE sales ADD CONSTRAINT sales_org_sale_number_unique UNIQUE (organization_id, sale_number)');

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