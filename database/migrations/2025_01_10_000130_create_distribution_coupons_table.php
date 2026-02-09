<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('neighborhood_association_id')->nullable()->constrained('loc_neighborhood_associations')->nullOnDelete();
            $table->string('coupon_code')->unique();
            $table->unsignedInteger('sequence')->nullable();
            $table->string('status')->default('unclaimed');
            $table->dateTime('claimed_at')->nullable();
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->time('pickup_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_coupons');
    }
};
