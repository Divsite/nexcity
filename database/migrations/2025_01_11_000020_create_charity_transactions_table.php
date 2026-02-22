<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charity_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('charity_type_id')->nullable()->constrained('charity_types')->nullOnDelete();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('payer_name')->nullable();
            $table->string('payer_phone')->nullable();
            $table->string('payer_email')->nullable();
            $table->string('payment_method')->nullable(); // cash, transfer, qris
            $table->foreignId('charity_payment_id')->nullable()->constrained('charity_payments')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('received_at')->nullable();
            $table->decimal('total_money', 18, 2)->nullable();
            $table->decimal('total_rice', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charity_transactions');
    }
};
