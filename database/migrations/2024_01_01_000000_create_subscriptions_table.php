<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            // NOTE: FK vers organizations est ajouté plus tard via une migration de correction
            // pour éviter l’erreur au déploiement quand la table organizations n’existe pas encore.
            $table->unsignedBigInteger('organization_id');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['paid', 'pending', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};