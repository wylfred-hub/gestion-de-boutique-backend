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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->onDelete('cascade');
            $table->enum('type', ['particulier', 'entreprise'])->default('particulier');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();       // ← AJOUT
            $table->string('company_name', 150)->nullable();
            $table->string('email', 150)->nullable()->unique(); // ← AJOUT
            $table->string('phone', 30)->nullable();            // ← AJOUT
            $table->text('address')->nullable();                // ← AJOUT
            $table->enum('category', ['standard', 'vip'])->default('standard');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
