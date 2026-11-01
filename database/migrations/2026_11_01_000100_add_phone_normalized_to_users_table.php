<?php

use App\Support\IndonesianPhone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A searchable copy of `users.phone`, for logging in with a WhatsApp code.
 *
 * Not unique: a ketua RT can hold a warga account and a pengurus account on the
 * same number. The login refuses to guess between them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_normalized', 20)->nullable()->after('phone')->index();
        });

        DB::table('users')
            ->whereNotNull('phone')
            ->orderBy('id')
            ->chunkById(500, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['phone_normalized' => IndonesianPhone::normalize($user->phone)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone_normalized']);
            $table->dropColumn('phone_normalized');
        });
    }
};
