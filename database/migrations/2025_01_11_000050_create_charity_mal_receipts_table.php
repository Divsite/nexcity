<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charity_mal_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charity_transaction_id')->constrained('charity_transactions')->cascadeOnDelete();
            $table->decimal('amount_money', 18, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charity_mal_receipts');
    }
};
