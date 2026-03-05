<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charity_transaction_payers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charity_transaction_id')->constrained('charity_transactions')->cascadeOnDelete();
            $table->string('payer_name');
            $table->string('payer_phone')->nullable();
            $table->string('payer_email')->nullable();
            $table->boolean('is_money')->default(true);
            $table->boolean('is_rice')->default(false);
            $table->unsignedInteger('multiplier_count')->nullable();
            $table->decimal('total_money', 18,  2)->default(0);
            $table->decimal('total_rice', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('charity_transaction_id', 'charity_transaction_payers_transaction_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charity_transaction_payers');
    }
};
