<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qurban_distribution_batches', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->nullable()->after('qurban_program_id')->index();
            $table->dateTime('claim_starts_at')->nullable()->after('distribution_date');
            $table->text('notes')->nullable()->after('location_label');
        });
    }

    public function down(): void
    {
        Schema::table('qurban_distribution_batches', function (Blueprint $table) {
            $table->dropColumn(['year', 'claim_starts_at', 'notes']);
        });
    }
};
