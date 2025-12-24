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
        Schema::create('form_process_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('process_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('linked_process_id')->nullable();
            $table->boolean('is_revert_submitter')->default(false);
            $table->boolean('is_end_process')->default(false);
            $table->boolean('comment_required')->default(false);
            $table->timestamps();

            $table->foreign('process_id')
                ->references('id')
                ->on('form_processes')
                ->cascadeOnDelete();

            $table->foreign('status_id')
                ->references('id')
                ->on('form_process_statuses')
                ->cascadeOnDelete();

            $table->foreign('linked_process_id')
                ->references('id')
                ->on('form_processes')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_process_actions');
    }
};
