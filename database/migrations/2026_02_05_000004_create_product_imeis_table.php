<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_imeis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('imei_1', 20);
            $table->string('imei_2', 20)->nullable();
            $table->string('status')->default('in_stock');
            $table->date('purchase_date')->nullable();
            $table->date('sold_date')->nullable();
            $table->foreignId('sale_item_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'imei_1']);
            $table->index('shop_id');
            $table->index(['shop_id', 'status']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_imeis');
    }
};
