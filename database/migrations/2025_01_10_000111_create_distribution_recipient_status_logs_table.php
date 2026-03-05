<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('distribution_recipient_status_logs')) {
            return;
        }

        Schema::create('distribution_recipient_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('distribution_recipient_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('status_note')->nullable();
            $table->string('status_reason')->nullable();
            $table->string('delivery_method')->nullable();
            $table->dateTime('reschedule_at')->nullable();
            $table->string('redirect_target')->nullable();
            $table->string('redirect_name')->nullable();
            $table->decimal('redirect_money', 18, 2)->default(0);
            $table->decimal('redirect_rice', 10, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('distribution_recipient_id', 'dist_recipient_status_logs_fk')
                ->references('id')
                ->on('distribution_recipients')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_recipient_status_logs');
    }
};
