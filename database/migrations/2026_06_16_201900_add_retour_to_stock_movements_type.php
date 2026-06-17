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
        // Postgres ne supporte pas MODIFY COLUMN (syntaxe MySQL).
        // On remplace le type ENUM en recréant le type puis en le rebranchant.
        DB::statement("ALTER TABLE stock_movements ALTER COLUMN type TYPE TEXT");
        DB::statement("DROP TYPE IF EXISTS stock_movements_type_enum");
        DB::statement("CREATE TYPE stock_movements_type_enum AS ENUM ('entree', 'sortie', 'retour')");
        DB::statement("ALTER TABLE stock_movements ALTER COLUMN type TYPE stock_movements_type_enum USING type::text::stock_movements_type_enum");

    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_movements ALTER COLUMN type TYPE TEXT");
        DB::statement("DROP TYPE IF EXISTS stock_movements_type_enum");
        DB::statement("CREATE TYPE stock_movements_type_enum AS ENUM ('entree', 'sortie')");
        DB::statement("ALTER TABLE stock_movements ALTER COLUMN type TYPE stock_movements_type_enum USING type::text::stock_movements_type_enum");

    }
};
