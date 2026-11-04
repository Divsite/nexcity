<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The price a buyer pays is two numbers, not one.
 *
 * `base_price` belongs to the vendor and is the same at every mosque — a mosque
 * cannot lower it, because it is not theirs. `service_fee` is the mosque's own,
 * for organising the slaughter, the distribution and the proof.
 *
 * Splitting them is what stops a price war. Identical animals sold as one
 * combined figure can only be told apart by who charges less, and mosques
 * undercutting each other spends exactly the trust that made them worth selling
 * through. Split, comparing two mosques means comparing their service.
 *
 * It also keeps the money honest downstream: the platform's cut and any
 * partner's fee come out of `service_fee` and never out of `base_price`, so
 * nothing a buyer thinks of as qurban money is ever shaved.
 *
 * Done now, with three rows in the table, rather than after a season's orders.
 *
 * See docs/architecture/qurban-business-model.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qurban_program_packages', function (Blueprint $table) {
            $table->decimal('base_price', 15, 2)->default(0)->after('description');
            $table->decimal('service_fee', 15, 2)->default(0)->after('base_price');
        });

        // Existing rows carry a combined figure. Attributing all of it to the
        // vendor is the safe reading: it leaves the buyer's total unchanged and
        // claims no fee the mosque never agreed to.
        DB::table('qurban_program_packages')->update([
            'base_price' => DB::raw('price'),
            'service_fee' => 0,
        ]);

        Schema::table('qurban_program_packages', function (Blueprint $table) {
            // `price` stays, as the stored total. Deriving it on every read
            // would mean every report and export re-implementing the same sum,
            // and one of them eventually getting it wrong.
            $table->decimal('price', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('qurban_program_packages', function (Blueprint $table) {
            $table->dropColumn(['base_price', 'service_fee']);
        });
    }
};
