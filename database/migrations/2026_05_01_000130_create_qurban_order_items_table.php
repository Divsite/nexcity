<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qurban_order_id')->constrained('qurban_orders')->cascadeOnDelete();
            $table->foreignId('qurban_program_package_id')->constrained('qurban_program_packages')->cascadeOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedSmallInteger('share_qty')->nullable();
            $table->decimal('price', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_order_items');
    }
};
