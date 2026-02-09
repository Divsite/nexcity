<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_corporate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('industry')->nullable();
            $table->year('established_year')->nullable();
            $table->unsignedInteger('employee_count')->nullable();
            $table->string('hr_contact_name')->nullable();
            $table->string('hr_contact_email')->nullable();
            $table->json('additional_info')->nullable();
            $table->timestamps();

            $table->unique('organization_id', 'org_corporate_profiles_org_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_corporate_profiles');
    }
};
