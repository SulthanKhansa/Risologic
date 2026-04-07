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
        Schema::table('recipe_items', function (Blueprint $table) {
            $table->unsignedBigInteger('raw_material_id')->nullable()->change();
            $table->foreignId('ingredient_product_id')->nullable()->constrained('products')->cascadeOnDelete()->after('raw_material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_items', function (Blueprint $table) {
            $table->dropForeign(['ingredient_product_id']);
            $table->dropColumn('ingredient_product_id');
            // Reverting raw_material_id to not nullable could fail if we dropped raw_material_id records, skipping for safety in down
        });
    }
};
