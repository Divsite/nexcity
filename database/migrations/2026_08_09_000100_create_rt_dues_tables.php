<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Iuran RT — monthly dues.
 *
 * Two tables rather than one because they answer different questions. A period
 * is what the RT decided: this month costs this much, due by this date. A bill
 * is what one household owes against it.
 *
 * Splitting them is what lets "who has not paid" be a plain query rather than a
 * reconstruction, which is the question an RT treasurer actually asks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rt_dues_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            // The rate the RT set for this month. Flat for everyone; a bill
            // keeps its own copy, see below.
            $table->decimal('amount', 18, 2);

            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // One rate per RT per month. Two competing rates for the same month
            // is not a state anyone could resolve later.
            $table->unique(['organization_id', 'year', 'month']);
        });

        Schema::create('rt_dues_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rt_dues_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->constrained('users')->cascadeOnDelete();

            // Copied from the period at creation, deliberately. If the RT later
            // corrects the month's rate, what someone already paid must not
            // change underneath them.
            $table->decimal('amount', 18, 2);

            // waived covers hardship — a widow, a family in difficulty. Without
            // it, those get marked "paid" and the books stop reflecting reality.
            $table->string('status')->default('pending'); // pending | paid | waived

            $table->dateTime('paid_at')->nullable();
            $table->string('payment_method')->nullable(); // cash | transfer | qris
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['rt_dues_period_id', 'resident_id']);
            // The resident portal always reads "my bills, newest first".
            $table->index(['resident_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rt_dues_bills');
        Schema::dropIfExists('rt_dues_periods');
    }
};
