<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qurban_order_id')->constrained('qurban_orders')->cascadeOnDelete();
            $table->string('payment_method')->default('cash')->index();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->string('reference_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_order_payments');
    }
};
