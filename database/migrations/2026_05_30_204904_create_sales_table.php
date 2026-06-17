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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')        // ← AJOUT
                ->constrained('organizations')
                ->onDelete('cascade');
            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->onDelete('set null');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict');
            $table->string('sale_number', 50)->nullable();
            $table->enum('status', [
                'encours',   // ← AJOUT
                'confirmee',           // ← AJOUT
                'annulee',
            ])->default('encours');
            $table->enum('discount_type', ['fixe', 'pourcentage'])->nullable();
            $table->decimal('discount_value', 10, 2)->default(0.00);
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
