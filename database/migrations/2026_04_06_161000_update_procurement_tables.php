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
        // 1. Drop old contact fields from suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['contact_person', 'phone']);
        });

        // 2. Clear old purchase data to avoid foreign key conflicts (since this is early dev)
        // Note: For production, we'd migrate data first.
        DB::table('purchase_items')->truncate();
        DB::table('purchases')->truncate();

        // 3. Move supplier_id from purchases to purchase_items
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null')->after('raw_material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }
};
