<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->enum('channel', ['whatsapp', 'sms']);
            $table->string('recipient_phone');
            $table->enum('message_type', ['booking_confirm', 'near_turn', 'prescription_link', 'followup_reminder', 'apology_day_off']);
            $table->text('content');
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('package_type', ['whatsapp', 'sms', 'subscription']);
            $table->integer('quantity_added');
            $table->decimal('amount_egp', 10, 2);
            $table->enum('payment_method', ['fawry', 'manual_super_admin'])->default('manual_super_admin');
            $table->string('fawry_reference')->nullable()->index();
            $table->enum('status', ['pending', 'paid', 'rejected'])->default('pending')->index();
            $table->foreignId('approved_by_admin_id')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_topups');
        Schema::dropIfExists('message_logs');
    }
};
