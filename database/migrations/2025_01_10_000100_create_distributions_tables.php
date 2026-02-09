<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('distribution_type_id')->constrained('m_distribution_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->text('status_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained('loc_countries')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('loc_provinces')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('loc_cities')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('loc_districts')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('loc_villages')->nullOnDelete();
            $table->foreignId('citizens_association_id')->nullable()->constrained('loc_citizens_associations')->nullOnDelete();
            $table->foreignId('neighborhood_association_id')->nullable()->constrained('loc_neighborhood_associations')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};
