<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('form_submission_current_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_submission_id');
            $table->unsignedBigInteger('status_id')->nullable();
            $table->unsignedBigInteger('process_id')->nullable();
            $table->string('comment')->nullable();
            $table->boolean('is_revert_submitter')->default(false);
            $table->boolean('is_end_process')->default(false);
            $table->timestamps();

            $table->foreign('form_submission_id')
                ->references('id')
                ->on('form_submissions')
                ->cascadeOnDelete();

            $table->foreign('process_id')
                ->references('id')
                ->on('form_processes')
                ->cascadeOnDelete();

            $table->foreign('status_id')
                ->references('id')
                ->on('form_process_statuses')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_submission_current_statuses');
    }
};
