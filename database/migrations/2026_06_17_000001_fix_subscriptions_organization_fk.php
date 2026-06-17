<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions') || !Schema::hasTable('organizations')) {
            return;
        }

        // Ajouter la FK seulement si elle n'existe pas déjà
        $fkExists = DB::select("
            SELECT 1 FROM information_schema.table_constraints
            WHERE constraint_type = 'FOREIGN KEY'
            AND table_name = 'subscriptions'
            AND constraint_name LIKE '%organization%'
        ");

        if (empty($fkExists)) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->foreign('organization_id')
                      ->references('id')
                      ->on('organizations')
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
        });
    }
};