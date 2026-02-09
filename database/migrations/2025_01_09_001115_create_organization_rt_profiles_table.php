<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_rt_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('period_start_date')->nullable();
            $table->date('period_end_date')->nullable();
            $table->string('office_phone')->nullable();
            $table->string('office_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('organization_id', 'org_rt_profiles_org_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_rt_profiles');
    }
};
