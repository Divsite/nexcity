<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charity_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('charity_transactions', 'is_package')) {
                $table->boolean('is_package')->default(false)->after('charity_payment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('charity_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('charity_transactions', 'is_package')) {
                $table->dropColumn('is_package');
            }
        });
    }
};
