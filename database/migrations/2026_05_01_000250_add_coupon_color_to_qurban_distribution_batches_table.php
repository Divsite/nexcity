<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qurban_distribution_batches', function (Blueprint $table) {
            $table->string('coupon_color', 20)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('qurban_distribution_batches', function (Blueprint $table) {
            $table->dropColumn('coupon_color');
        });
    }
};
