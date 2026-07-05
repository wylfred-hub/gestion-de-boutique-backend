<?php

// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
//     /**
//      * Run the migrations.
//      */
//     public function up(): void
//     {
//         Schema::create('stock_movements', function (Blueprint $table) {
//             $table->id();
//             $table->foreignId('product_id')
//                   ->constrained('products')
//                   ->onDelete('restrict');
//             $table->foreignId('user_id')
//                   ->constrained('users')
//                   ->onDelete('restrict');
//             $table->enum('type', ['entree', 'sortie']);
//             $table->integer('quantity');           // peut être négatif
//             $table->integer('quantity_before');    // stock avant
//             $table->integer('quantity_after');     // stock après
//             $table->unsignedBigInteger('reference_id')->nullable();   // ID vente liée
//             $table->string('reference_type', 100)->nullable();        // ex: 'sale'
//             $table->timestamps();
//         });
//     }

//     /**
//      * Reverse the migrations.
//      */
//     public function down(): void
//     {
//         Schema::dropIfExists('stock_movements');
//     }
// };


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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->onDelete('restrict');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict');

            $table->enum('type', ['entree', 'sortie', 'retour']);

            $table->integer('quantity');           // peut être négatif
            $table->integer('quantity_before');    // stock avant
            $table->integer('quantity_after');     // stock après

            $table->unsignedBigInteger('reference_id')->nullable();   // ID vente liée
            $table->string('reference_type', 100)->nullable();        // ex: 'sale'

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};