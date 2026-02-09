<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_institution_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('institution_type')->nullable();
            $table->string('accreditation_status')->nullable();
            $table->year('established_year')->nullable();
            $table->unsignedInteger('student_count')->nullable();
            $table->json('additional_info')->nullable();
            $table->timestamps();

            $table->unique('organization_id', 'org_institution_profiles_org_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_institution_profiles');
    }
};
