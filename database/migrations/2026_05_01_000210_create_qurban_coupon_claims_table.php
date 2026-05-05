<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_coupon_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qurban_distribution_batch_id')->nullable()->constrained('qurban_distribution_batches')->nullOnDelete();
            $table->foreignId('qurban_coupon_id')->nullable()->constrained('qurban_coupons')->nullOnDelete();
            $table->string('scanned_code')->nullable()->index();
            $table->foreignId('claimed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('claimed_at')->nullable();
            $table->string('scan_result')->index();
            $table->foreignId('scanner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('scan_latitude', 10, 7)->nullable();
            $table->decimal('scan_longitude', 10, 7)->nullable();
            $table->string('scan_location_label')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['qurban_coupon_id', 'scan_result'], 'qurban_coupon_claims_coupon_result_index');
            $table->index(['qurban_distribution_batch_id', 'scan_result'], 'qurban_coupon_claims_batch_result_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_coupon_claims');
    }
};
