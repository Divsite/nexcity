<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_distribution_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('qurban_program_id')->nullable()->constrained('qurban_programs')->nullOnDelete();
            $table->string('title');
            $table->date('distribution_date')->nullable();
            $table->string('location_label')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('loc_countries')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('loc_provinces')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('loc_cities')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('loc_districts')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('loc_villages')->nullOnDelete();
            $table->foreignId('citizens_association_id')->nullable()->constrained('loc_citizens_associations')->nullOnDelete();
            $table->foreignId('neighborhood_association_id')->nullable()->constrained('loc_neighborhood_associations')->nullOnDelete();
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'qurban_program_id'], 'qurban_dist_batches_org_program_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_distribution_batches');
    }
};
