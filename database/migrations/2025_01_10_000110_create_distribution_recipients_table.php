<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('distribution_class_id')->nullable()->constrained('m_distribution_classes')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('status_note')->nullable();
            $table->dateTime('distributed_at')->nullable();
            $table->timestamps();

            $table->unique(['distribution_id', 'resident_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_recipients');
    }
};
