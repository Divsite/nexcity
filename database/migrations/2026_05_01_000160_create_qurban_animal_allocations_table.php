<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_animal_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qurban_animal_id')->constrained('qurban_animals')->cascadeOnDelete();
            $table->foreignId('qurban_order_item_id')->nullable()->constrained('qurban_order_items')->nullOnDelete();
            $table->foreignId('qurban_program_id')->constrained('qurban_programs')->cascadeOnDelete();
            $table->unsignedSmallInteger('share_index')->nullable();
            $table->decimal('allocated_weight', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['qurban_program_id', 'qurban_animal_id'], 'qurban_alloc_program_animal_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_animal_allocations');
    }
};
