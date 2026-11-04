<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A patungan line a warga took from the app is held, not owned, until the
 * first deposit arrives.
 *
 * Without this anyone can tap "ambil bagian" on every free line of every cow
 * and never pay, and the takmir cannot order an animal that six names are
 * sitting on. `hold_expires_at` is when an unpaid line is released;
 * `expired_at` records that it was. See App\Actions\Qurban\ReleaseUnpaidHolds.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qurban_orders', function (Blueprint $table) {
            $table->timestamp('hold_expires_at')->nullable()->after('status');
            $table->timestamp('expired_at')->nullable()->after('hold_expires_at');
            $table->index(['status', 'hold_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('qurban_orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'hold_expires_at']);
            $table->dropColumn(['hold_expires_at', 'expired_at']);
        });
    }
};
