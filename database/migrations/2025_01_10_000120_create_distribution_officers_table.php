<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('officer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('neighborhood_association_id')->nullable()->constrained('loc_neighborhood_associations')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['distribution_id', 'officer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_officers');
    }
};
