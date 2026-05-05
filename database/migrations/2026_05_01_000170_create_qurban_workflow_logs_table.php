<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qurban_program_id')->constrained('qurban_programs')->cascadeOnDelete();
            $table->foreignId('qurban_animal_id')->nullable()->constrained('qurban_animals')->nullOnDelete();
            $table->foreignId('qurban_order_id')->nullable()->constrained('qurban_orders')->nullOnDelete();
            $table->string('stage')->index();
            $table->text('stage_note')->nullable();
            $table->string('media_path')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('performed_at')->nullable();
            $table->timestamps();

            $table->index(['qurban_program_id', 'stage'], 'qurban_workflow_program_stage_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_workflow_logs');
    }
};
