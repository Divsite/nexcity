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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->foreignId('country_id')->nullable()->constrained('loc_countries')->nullOnDelete();
            $table->foreignId('organization_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('loc_provinces')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('loc_cities')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('loc_districts')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('loc_villages')->nullOnDelete();
            $table->foreignId('citizens_association_id')->nullable()->constrained('loc_citizens_associations')->nullOnDelete();
            $table->foreignId('neighborhood_association_id')->nullable()->constrained('loc_neighborhood_associations')->nullOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('status')->default('active');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
