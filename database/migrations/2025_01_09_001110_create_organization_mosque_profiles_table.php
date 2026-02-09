<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_mosque_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->year('built_year')->nullable();
            $table->decimal('floor_area', 10, 2)->nullable();
            $table->unsignedSmallInteger('floor_count')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignId('ownership_status_id')->constrained('m_ownership_statuses');
            $table->string('owner_name')->nullable();
            $table->decimal('property_value', 18, 2)->nullable();
            $table->string('certification_number')->nullable();
            $table->string('certification_status')->nullable();
            $table->timestamps();

            $table->unique('organization_id', 'org_mosque_profiles_org_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_mosque_profiles');
    }
};
