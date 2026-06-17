<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Postgres ne supporte pas MODIFY COLUMN (syntaxe MySQL).
        
        // 1. Supprimer la contrainte de vérification créée par Laravel (nom standard: table_colonne_check)
        DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_type_check');
        
        // 2. Supprimer la valeur par défaut pour éviter les erreurs de cast pendant le changement de type
        DB::statement('ALTER TABLE stock_movements ALTER COLUMN type DROP DEFAULT');

        // 3. Conversion temporaire en TEXT
        DB::statement('ALTER TABLE stock_movements ALTER COLUMN type TYPE TEXT');
        
        // 4. Recréation du type ENUM
        DB::statement('DROP TYPE IF EXISTS stock_movements_type_enum');
        DB::statement("CREATE TYPE stock_movements_type_enum AS ENUM ('entree', 'sortie', 'retour')");

        // 5. Conversion finale vers le nouvel ENUM
        DB::statement('ALTER TABLE stock_movements ALTER COLUMN type TYPE stock_movements_type_enum USING type::text::stock_movements_type_enum');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE stock_movements DROP CONSTRAINT IF EXISTS stock_movements_type_check');
        DB::statement('ALTER TABLE stock_movements ALTER COLUMN type DROP DEFAULT');
        DB::statement('ALTER TABLE stock_movements ALTER COLUMN type TYPE TEXT');
        DB::statement('DROP TYPE IF EXISTS stock_movements_type_enum');
        DB::statement("CREATE TYPE stock_movements_type_enum AS ENUM ('entree', 'sortie')");
        DB::statement('ALTER TABLE stock_movements ALTER COLUMN type TYPE stock_movements_type_enum USING type::stock_movements_type_enum');
    }
};
