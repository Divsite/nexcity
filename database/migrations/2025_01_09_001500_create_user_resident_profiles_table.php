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
        Schema::create('user_resident_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained('loc_countries')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('loc_provinces')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('loc_cities')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('loc_districts')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('loc_villages')->nullOnDelete();
            $table->foreignId('citizens_association_id')->nullable()->constrained('loc_citizens_associations')->nullOnDelete();
            $table->foreignId('neighborhood_association_id')->nullable()->constrained('loc_neighborhood_associations')->nullOnDelete();
            $table->uuid('qr_token')->nullable()->unique();
            $table->string('barcode_path')->nullable();
            $table->timestamp('qr_generated_at')->nullable();
            $table->string('family_card_number')->nullable();
            $table->string('national_id_number')->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->foreignId('residence_status_id')->nullable()->constrained('m_residence_statuses')->nullOnDelete();
            $table->foreignId('marital_status_id')->nullable()->constrained('m_marital_statuses')->nullOnDelete();
            $table->foreignId('education_id')->nullable()->constrained('m_educations')->nullOnDelete();
            $table->foreignId('education_major_id')->nullable()->constrained('m_education_majors')->nullOnDelete();
            $table->foreignId('religion_id')->nullable()->constrained('m_religions')->nullOnDelete();
            $table->string('occupation')->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->boolean('is_head_family')->default(false);
            $table->unsignedInteger('family_members_count')->default(0);
            $table->json('interests')->nullable();
            $table->json('talents')->nullable();
            $table->string('ktp_photo_path')->nullable();
            $table->json('house_photo_paths')->nullable();
            $table->string('address_line')->nullable();
            $table->json('additional_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_resident_profiles');
    }
};
