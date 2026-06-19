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
        Schema::table('promos', function (Blueprint $table) {
            // Ubah kolom code menjadi nullable (promo tidak pakai kode)
            $table->string('code', 50)->nullable()->change();
            // Tambah kolom min_purchase untuk syarat subtotal minimal
            $table->decimal('min_purchase', 15, 2)->default(0)->after('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn('min_purchase');
            $table->string('code', 50)->unique()->change();
        });
    }
};
