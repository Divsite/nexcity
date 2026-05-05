<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('animal_type')->index();
            $table->string('animal_code');
            $table->string('ear_tag_code')->nullable();
            $table->string('qr_code')->nullable();
            $table->string('gender')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('estimated_meat_weight', 10, 2)->nullable();
            $table->string('color')->nullable();
            $table->unsignedSmallInteger('age_months')->nullable();
            $table->string('health_status')->nullable();
            $table->string('procurement_type')->default('owned')->index();
            $table->string('vendor_name_snapshot')->nullable();
            $table->decimal('purchase_price', 18, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('status')->default('available')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'animal_code'], 'qurban_animals_org_code_unique');
            $table->unique(['organization_id', 'qr_code'], 'qurban_animals_org_qr_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_animals');
    }
};
