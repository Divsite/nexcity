<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qurban_distribution_batch_id')->constrained('qurban_distribution_batches')->cascadeOnDelete();
            $table->foreignId('qurban_beneficiary_id')->nullable()->constrained('qurban_beneficiaries')->nullOnDelete();
            $table->string('coupon_code')->unique();
            $table->string('qr_code')->nullable()->unique();
            $table->string('package_label')->nullable();
            $table->decimal('meat_weight', 10, 2)->nullable();
            $table->string('status')->default('issued')->index();
            $table->timestamps();

            $table->index(['qurban_distribution_batch_id', 'status'], 'qurban_coupons_batch_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_coupons');
    }
};
