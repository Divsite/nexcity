<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            if (!Schema::hasColumn('forms', 'webhook_url')) {
                $table->string('webhook_url')->nullable()->after('properties');
            }

            if (!Schema::hasColumn('forms', 'use_current_url')) {
                $table->boolean('use_current_url')->default(false)->after('webhook_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            if (Schema::hasColumn('forms', 'webhook_url')) {
                $table->dropColumn('webhook_url');
            }

            if (Schema::hasColumn('forms', 'use_current_url')) {
                $table->dropColumn('use_current_url');
            }
        });
    }
};
