<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->decimal('price_per_unit', 12, 2)->default(0);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('hpp', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropColumn('price_per_unit');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('hpp');
        });
    }
};
