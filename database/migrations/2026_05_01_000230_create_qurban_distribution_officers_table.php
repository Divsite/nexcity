<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_distribution_officers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qurban_distribution_batch_id');
            $table->unsignedBigInteger('officer_id');
            $table->timestamps();

            $table->unique(['qurban_distribution_batch_id', 'officer_id'], 'qurban_dist_officers_batch_officer_unique');
            $table->foreign('qurban_distribution_batch_id', 'qurban_dist_officers_batch_fk')
                ->references('id')
                ->on('qurban_distribution_batches')
                ->cascadeOnDelete();
            $table->foreign('officer_id', 'qurban_dist_officers_officer_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_distribution_officers');
    }
};
