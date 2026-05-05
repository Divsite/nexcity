<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_program_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qurban_program_id')->constrained('qurban_programs')->cascadeOnDelete();
            $table->string('animal_type')->index();
            $table->string('package_type')->default('full')->index();
            $table->unsignedSmallInteger('share_count')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('target_weight_min', 10, 2)->nullable();
            $table->decimal('target_weight_max', 10, 2)->nullable();
            $table->decimal('price', 18, 2)->default(0);
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('remaining_quota')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_program_packages');
    }
};
