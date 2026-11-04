<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A warga saying "this is wrong about me", for their RT to act on.
 *
 * The warga never edits the record directly. The mosque picks zakat and qurban
 * recipients from this data, so a change goes through the RT, which knows the
 * household. Applying a correction is the RT editing the resident on the web
 * and then marking the request done here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_data_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // The RT that owns the record, so its officers see the request.
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('field', 40);
            $table->string('proposed_value', 255)->nullable();
            $table->text('note')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_data_corrections');
    }
};
