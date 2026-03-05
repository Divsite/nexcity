<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_fund_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('distribution_type_id')->nullable()->constrained('m_distribution_types')->nullOnDelete();
            $table->foreignId('distribution_class_id')->nullable()->constrained('distribution_classes')->nullOnDelete();
            $table->foreignId('neighborhood_association_id')->nullable()->constrained('loc_neighborhood_associations')->nullOnDelete();
            $table->unsignedSmallInteger('year')->nullable();
            $table->json('priority_charity_type_ids')->nullable();
            $table->boolean('enforce_priority')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_fund_settings');
    }
};
