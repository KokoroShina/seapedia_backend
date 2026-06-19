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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('address_id')->constrained('addresses')->noActionOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->noActionOnDelete();
            $table->foreignId('promo_id')->nullable()->constrained('promos')->noActionOnDelete();
            $table->string('delivery_method');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('delivery_fee', 15, 2);
            $table->decimal('ppn', 15, 2);
            $table->decimal('total', 15, 2);
            $table->string('status')->default('sedang_dikemas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
