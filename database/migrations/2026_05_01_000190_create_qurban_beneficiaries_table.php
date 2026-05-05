<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name_snapshot');
            $table->string('phone_snapshot')->nullable();
            $table->text('address_snapshot')->nullable();
            $table->string('category')->default('manual')->index();
            $table->foreignId('citizens_association_id')->nullable()->constrained('loc_citizens_associations')->nullOnDelete();
            $table->foreignId('neighborhood_association_id')->nullable()->constrained('loc_neighborhood_associations')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'resident_id'], 'qurban_beneficiaries_org_resident_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_beneficiaries');
    }
};
