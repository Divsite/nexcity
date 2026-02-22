<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charity_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('charity_type_source_id')->nullable()->constrained('m_charity_type_sources')->nullOnDelete();
            $table->unsignedSmallInteger('year')->nullable();
            $table->decimal('min_amount', 18, 2)->nullable();
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->boolean('is_rice')->default(false);
            $table->decimal('total_rice', 10, 2)->nullable();
            $table->decimal('package_amount', 18, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'charity_type_source_id', 'year'], 'charity_type_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charity_types');
    }
};
