<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Iuran RT, shaped after the printed card an RT actually uses.
 *
 * The first cut modelled one flat rate per month. A real card (RT 04/03 Pondok
 * Jati Utara, 2026) shows three things that did not fit:
 *
 *   - **Two rates side by side** — Ber KK Rp 20.000, Tidak Ber KK Rp 15.000.
 *   - **A whole year at once** — twelve rows printed in advance, not a month
 *     opened at a time.
 *   - **Dues are a package**, not a number: santunan sosial, kain kafan, hansip,
 *     sarpras. The card lists them under the table.
 *
 * And beyond the monthly card there are one-off collections — HUT RI, a
 * renovation fund, a religious event — which differ from RT to RT.
 *
 * Hence three tables instead of two:
 *
 *   scheme  — what is being collected, and what it funds
 *     rate  — how much, per golongan (or one flat rate for everyone)
 *   period  — one month of a monthly scheme, or the single date of a one-off
 *     bill  — what one household owes against a period
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rt_dues_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            // monthly → twelve periods a year; seasonal → one, dated when the
            // RT decides. Anything an RT invents later is a new row, not a
            // code change.
            $table->string('type')->default('monthly');

            $table->unsignedSmallInteger('year');

            // What the money pays for, as the card prints it. Descriptive: the
            // RT writes its own list, and no two RTs have the same one.
            $table->text('programs')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'year', 'name']);
        });

        Schema::create('rt_dues_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_dues_scheme_id')->constrained()->cascadeOnDelete();

            // Null means "everyone pays this" — the ordinary shape for a one-off
            // collection, where nobody is separated by golongan. A scheme that
            // later wants tiers just gains rows; nothing here has to change.
            $table->string('tier')->nullable();

            $table->string('label');
            $table->decimal('amount', 18, 2);

            // Which rate applies to a resident whose golongan was never set.
            // This matters more than it looks: only 4 of 207 residents have a
            // family card number on file, so most people have no golongan yet
            // and must still be billable.
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->unique(['rt_dues_scheme_id', 'tier']);
        });

        Schema::create('rt_dues_periods_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_dues_scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');

            // Null for a one-off: "Iuran HUT RI" has no month, only a date.
            $table->unsignedTinyInteger('month')->nullable();

            $table->string('label')->nullable();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['rt_dues_scheme_id', 'year', 'month']);
        });

        Schema::dropIfExists('rt_dues_bills');
        Schema::dropIfExists('rt_dues_periods');
        Schema::rename('rt_dues_periods_new', 'rt_dues_periods');

        Schema::create('rt_dues_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_dues_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->constrained('users')->cascadeOnDelete();

            // Both copied at issue time, deliberately. Correcting a rate later
            // must not rewrite what a household was already told they owed, and
            // the golongan is part of that story — "why was I charged 20.000?"
            // has to stay answerable.
            $table->decimal('amount', 18, 2);
            $table->string('tier')->nullable();

            $table->string('status')->default('pending'); // pending | paid | waived
            $table->dateTime('paid_at')->nullable();

            // cash is the normal case and will stay so: an officer walks the
            // block and signs the card. Online payment is a later addition to
            // this column, not a replacement for it.
            $table->string('payment_method')->nullable();

            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['rt_dues_period_id', 'resident_id']);
            $table->index(['resident_id', 'status']);
        });

        Schema::table('user_resident_profiles', function (Blueprint $table) {
            // The household's golongan, stored rather than derived. A family
            // card number is a hint the treasurer weighs, not the answer —
            // most profiles do not have one, and a household's status does not
            // change month to month, so deciding it once is both cheaper and
            // more honest than recomputing it every billing run.
            $table->string('dues_tier')->nullable()->after('family_card_number');
        });
    }

    public function down(): void
    {
        Schema::table('user_resident_profiles', function (Blueprint $table) {
            $table->dropColumn('dues_tier');
        });

        Schema::dropIfExists('rt_dues_bills');
        Schema::dropIfExists('rt_dues_periods');
        Schema::dropIfExists('rt_dues_rates');
        Schema::dropIfExists('rt_dues_schemes');
    }
};
