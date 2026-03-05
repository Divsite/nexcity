<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->text('recipient_address')->nullable();
            $table->foreignId('distribution_class_id')->nullable()->constrained('distribution_classes')->nullOnDelete();
            $table->string('group_label')->nullable();
            $table->decimal('amount_money', 18, 2)->nullable();
            $table->decimal('amount_rice', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->text('status_note')->nullable();
            $table->dateTime('distributed_at')->nullable();
            $table->dateTime('reschedule_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['distribution_id', 'resident_id']);
        });

        Schema::create('distribution_recipient_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('distribution_recipient_id');
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('disk')->nullable()->default('public');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('distribution_recipient_id', 'dist_recipient_attach_recipient_fk')
                ->references('id')
                ->on('distribution_recipients')
                ->cascadeOnDelete();
        });

        Schema::create('distribution_recipient_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('distribution_recipient_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('status_note')->nullable();
            $table->string('status_reason')->nullable();
            $table->string('delivery_method')->nullable();
            $table->dateTime('reschedule_at')->nullable();
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
        Schema::dropIfExists('distribution_recipient_attachments');
        Schema::dropIfExists('distribution_recipients');
    }
};
