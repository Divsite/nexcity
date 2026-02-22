<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charity_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('charity_transactions', 'use_same_package_amount')) {
                $table->boolean('use_same_package_amount')->default(false)->after('is_package');
            }

            if (! Schema::hasColumn('charity_transactions', 'package_amount_each')) {
                $table->decimal('package_amount_each', 18, 2)->nullable()->after('use_same_package_amount');
            }

            if (! Schema::hasColumn('charity_transactions', 'package_members_count')) {
                $table->unsignedInteger('package_members_count')->nullable()->after('package_amount_each');
            }
        });
    }

    public function down(): void
    {
        Schema::table('charity_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('charity_transactions', 'package_members_count')) {
                $table->dropColumn('package_members_count');
            }

            if (Schema::hasColumn('charity_transactions', 'package_amount_each')) {
                $table->dropColumn('package_amount_each');
            }

            if (Schema::hasColumn('charity_transactions', 'use_same_package_amount')) {
                $table->dropColumn('use_same_package_amount');
            }
        });
    }
};
