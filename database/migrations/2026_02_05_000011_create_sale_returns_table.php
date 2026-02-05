<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('return_number', 50);
            $table->date('return_date');
            $table->decimal('total_refund', 12, 2)->default(0);
            $table->string('refund_method')->default('cash');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'return_number']);
            $table->index('shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
