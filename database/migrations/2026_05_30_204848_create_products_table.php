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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')      // ← AJOUT OBLIGATOIRE
                ->constrained('organizations')
                ->onDelete('cascade');
            $table->foreignId('category_id')
                ->constrained('categories')
                ->onDelete('restrict');
            $table->string('name', 150);
            // Référence unique par organisation (utilisée par le modèle Product)
            $table->string('reference', 255);
            $table->unique(['organization_id', 'reference']);


            $table->text('description')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0.00);
            $table->decimal('selling_price', 10, 2)->default(0.00);
            $table->string('unit', 50)->default('pièce');
            $table->string('barcode', 100)->unique()->nullable();
            $table->string('image', 255)->nullable();
            $table->unsignedInteger('stock_alert')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
