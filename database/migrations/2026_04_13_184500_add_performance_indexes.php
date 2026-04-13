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
        Schema::table('users', function (Blueprint $table) {
            $table->index('username');
            $table->index('email');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('customer_name');
            $table->index('status');
            $table->index('created_at');
            $table->index('product_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('type');
        });

        Schema::table('production_events', function (Blueprint $table) {
            $table->index('production_date');
            $table->index('status');
            $table->index('product_id');
        });

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->index('name');
            $table->index('brand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['username']);
            $table->dropIndex(['email']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['customer_name']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['product_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['type']);
        });

        Schema::table('production_events', function (Blueprint $table) {
            $table->dropIndex(['production_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['product_id']);
        });

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['brand']);
        });
    }
};
