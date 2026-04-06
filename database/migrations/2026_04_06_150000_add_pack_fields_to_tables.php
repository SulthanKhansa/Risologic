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
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->decimal('pack_price', 15, 2)->nullable()->after('price_per_unit');
            $table->decimal('pack_size', 15, 2)->nullable()->after('pack_price');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('pack_price', 15, 2)->nullable()->after('unit_price');
            $table->decimal('pack_size', 15, 2)->nullable()->after('pack_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropColumn(['pack_price', 'pack_size']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['pack_price', 'pack_size']);
        });
    }
};
