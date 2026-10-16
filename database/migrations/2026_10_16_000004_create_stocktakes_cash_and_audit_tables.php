<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocktakes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('scope')->default('all');
            $table->enum('status', ['draft', 'confirmed'])->default('draft');
            $table->foreignUuid('counted_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignUuid('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stocktake_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stocktake_id')->constrained('stocktakes')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('system_qty', 12, 3)->default(0);
            $table->decimal('physical_qty', 12, 3)->default(0);
            $table->string('reason', 30)->nullable(); // lost, damaged, input_error, expired, other
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_closings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('business_date')->unique();
            $table->bigInteger('opening_cash')->default(0);
            $table->bigInteger('expected_cash')->default(0);
            $table->bigInteger('actual_cash')->default(0);
            $table->bigInteger('diff')->default(0);
            $table->text('note')->nullable();
            $table->foreignUuid('closed_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamp('closed_at')->useCurrent();
            $table->foreignUuid('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('spent_on');
            $table->unsignedBigInteger('amount');
            $table->string('description');
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('entity', 50);
            $table->string('entity_id', 36)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('cash_closings');
        Schema::dropIfExists('stocktake_items');
        Schema::dropIfExists('stocktakes');
    }
};
