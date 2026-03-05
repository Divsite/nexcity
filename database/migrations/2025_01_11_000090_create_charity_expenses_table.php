<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charity_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('charity_type_id')->nullable()->constrained('charity_types')->nullOnDelete();
            $table->string('source_type')->default('charity');
            $table->string('source_name')->nullable();
            $table->string('expense_type')->default('operational');
            $table->string('expense_type_name')->nullable();
            $table->decimal('amount', 18, 2)->default(0);
            $table->date('expense_date')->nullable();
            $table->integer('year')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charity_expenses');
    }
};
