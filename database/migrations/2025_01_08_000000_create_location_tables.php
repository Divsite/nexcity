<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loc_countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('loc_provinces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('loc_countries')->cascadeOnDelete();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('loc_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('loc_provinces')->cascadeOnDelete();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('loc_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('loc_cities')->cascadeOnDelete();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('loc_villages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('loc_districts')->cascadeOnDelete();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('postal_code', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('loc_citizens_associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained('loc_villages')->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('number', 10)->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('leader_name')->nullable();
            $table->string('leader_phone')->nullable();
            $table->date('start_period')->nullable();
            $table->date('end_period')->nullable();
            $table->timestamps();

            $table->unique(['village_id', 'slug'], 'loc_citizens_village_slug_unique');
        });

        Schema::create('loc_neighborhood_associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizens_association_id')->constrained('loc_citizens_associations')->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('number', 10)->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('leader_name')->nullable();
            $table->string('leader_phone')->nullable();
            $table->date('start_period')->nullable();
            $table->date('end_period')->nullable();
            $table->timestamps();

            $table->unique(['citizens_association_id', 'slug'], 'loc_neighborhoods_ca_slug_unique');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('loc_neighborhood_associations');
        Schema::dropIfExists('loc_citizens_associations');
        Schema::dropIfExists('loc_villages');
        Schema::dropIfExists('loc_districts');
        Schema::dropIfExists('loc_cities');
        Schema::dropIfExists('loc_provinces');
        Schema::dropIfExists('loc_countries');
    }
};
