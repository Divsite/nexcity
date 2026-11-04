<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Whose charity this was, when the payer has an account.
 *
 * Until now a transaction carried only the name and phone the officer typed,
 * so a warga who paid zakat at the counter never saw it in their own app.
 * Nullable: most payers at a village mosque have no account and never will,
 * and the typed name stays the record of who paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('charity_transactions', function (Blueprint $table) {
            $table->foreignId('payer_user_id')
                ->nullable()
                ->after('payer_email')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('charity_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payer_user_id');
        });
    }
};
