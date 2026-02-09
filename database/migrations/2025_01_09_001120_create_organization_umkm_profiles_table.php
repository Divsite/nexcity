<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_umkm_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('business_type')->nullable();
            $table->string('owner_name')->nullable();
            $table->year('established_year')->nullable();
            $table->unsignedSmallInteger('employee_count')->nullable();
            $table->json('additional_info')->nullable();
            $table->timestamps();

            $table->unique('organization_id', 'org_umkm_profiles_org_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_umkm_profiles');
    }
};
