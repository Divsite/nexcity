<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Sapi 1" exists as an idea before it exists as an animal.
 *
 * A mosque opens the patungan by naming the animals it intends to buy — Sapi 1,
 * Sapi 2, Sapi 3 — and people sign up to them one slot at a time. The real cow
 * is procured once the seven are full. So the record has to exist before the
 * animal does, holding nothing but a name and seven empty places.
 *
 * That is how takmir already work: a sheet of paper with a heading and seven
 * lines. `qurban_animal_allocations.share_index` was already designed for those
 * lines; what was missing was somewhere for the sheet itself to live before an
 * animal was bought.
 *
 * Two columns:
 *
 * - `qurban_program_id` so an animal with nobody signed up yet still belongs to
 *   a season. Derived from its allocations it would be invisible until someone
 *   joined — the exact moment it most needs to be visible.
 * - `share_slots` because it is a property of this animal, not of its species.
 *   A cow is seven; a goat is one; and the number is what the board draws.
 *
 * See docs/architecture/qurban-business-model.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qurban_animals', function (Blueprint $table) {
            $table->foreignId('qurban_program_id')
                ->nullable()
                ->after('organization_id')
                ->constrained()
                ->nullOnDelete();

            $table->unsignedTinyInteger('share_slots')
                ->default(1)
                ->after('animal_type');

            // The board reads "every animal in this programme, in order".
            $table->index(['qurban_program_id', 'animal_code']);
        });

        // Existing rows predate the board. A cow is shared seven ways and
        // anything else is not shared at all — the rule the fiqh already sets,
        // not a default we invented.
        DB::table('qurban_animals')
            ->where('animal_type', 'cow')
            ->update(['share_slots' => 7]);

        DB::table('qurban_animals')
            ->where('animal_type', 'buffalo')
            ->update(['share_slots' => 7]);
    }

    public function down(): void
    {
        Schema::table('qurban_animals', function (Blueprint $table) {
            $table->dropIndex(['qurban_program_id', 'animal_code']);
            $table->dropConstrainedForeignId('qurban_program_id');
            $table->dropColumn('share_slots');
        });
    }
};
