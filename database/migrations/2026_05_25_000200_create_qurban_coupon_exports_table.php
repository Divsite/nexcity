<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_coupon_exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->enum('type', ['single', 'all'])->default('single');
            $table->enum('status', ['pending', 'processing', 'ready', 'failed'])->default('pending');
            $table->string('file_path')->nullable();
            $table->string('error_message')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('batch_id')->references('id')->on('qurban_distribution_batches')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_coupon_exports');
    }
};
