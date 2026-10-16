<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->unsignedInteger('lead_time_days')->default(1);
            $table->decimal('safety_stock', 12, 3)->default(0);
            $table->unsignedInteger('expiry_warning_days')->default(14);
            $table->unsignedInteger('overstock_days')->default(60);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->foreignUuid('category_id')->constrained('categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('base_unit', 30);
            $table->unsignedBigInteger('sell_price')->default(0);
            $table->unsignedBigInteger('last_buy_price')->default(0);
            $table->decimal('min_stock', 12, 3)->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->decimal('safety_stock', 12, 3)->nullable();
            $table->boolean('has_expiry')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Unique name among active products (case-insensitive in MySQL default utf8mb4 collation)
            $table->index(['name', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
