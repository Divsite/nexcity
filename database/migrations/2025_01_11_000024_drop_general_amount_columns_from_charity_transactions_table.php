<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charity_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('charity_transactions', 'received_at')) {
                $table->dropColumn('received_at');
            }

            if (Schema::hasColumn('charity_transactions', 'total_money')) {
                $table->dropColumn('total_money');
            }

            if (Schema::hasColumn('charity_transactions', 'total_rice')) {
                $table->dropColumn('total_rice');
            }

            if (Schema::hasColumn('charity_transactions', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('charity_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('charity_transactions', 'received_at')) {
                $table->dateTime('received_at')->nullable()->after('received_by');
            }

            if (! Schema::hasColumn('charity_transactions', 'total_money')) {
                $table->decimal('total_money', 18, 2)->nullable()->after('received_at');
            }

            if (! Schema::hasColumn('charity_transactions', 'total_rice')) {
                $table->decimal('total_rice', 10, 2)->nullable()->after('total_money');
            }

            if (! Schema::hasColumn('charity_transactions', 'notes')) {
                $table->text('notes')->nullable()->after('total_rice');
            }
        });
    }
};
