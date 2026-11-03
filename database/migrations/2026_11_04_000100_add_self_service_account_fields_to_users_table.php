<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts a person opens and closes themselves.
 *
 * `registered_via` tells a self-registered account ("self", from the app) from
 * one an RT or a mosque created (null). `closed_at` marks an account its owner
 * deleted: the row stays, because charity, iuran and qurban records point at
 * it and the organizations' books must still add up, but nothing can sign in
 * to it and its contact details are gone. See App\Actions\Accounts\CloseOwnAccount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registered_via', 16)->nullable()->after('phone_normalized');
            $table->timestamp('closed_at')->nullable()->after('registered_via');
            $table->index('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['closed_at']);
            $table->dropColumn(['registered_via', 'closed_at']);
        });
    }
};
