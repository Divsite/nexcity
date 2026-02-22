<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('distribution_class_source_id')->nullable()->constrained('m_distribution_class_sources')->nullOnDelete();
            $table->unsignedSmallInteger('year')->nullable();
            $table->decimal('get_money', 18, 2)->nullable();
            $table->unsignedInteger('get_rice')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'distribution_class_source_id', 'year'], 'distribution_class_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_classes');
    }
};
