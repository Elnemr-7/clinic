<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('specialty_key', ['general', 'dental', 'pediatrics', 'dermatology', 'obgyn', 'orthopedics'])->default('general');
            $table->string('title_ar')->nullable();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->dateTime('subscription_ends_at')->nullable();
            $table->enum('status', ['active', 'suspended', 'expired'])->default('active')->index();

            // Limits & Wallets (Overridden / Managed by Super Admin)
            $table->integer('max_clinics_override')->nullable(); // -1 = Unlimited, null = inherit plan
            $table->boolean('whatsapp_enabled')->default(false);
            $table->integer('whatsapp_balance')->default(0);
            $table->integer('whatsapp_used_this_month')->default(0);
            $table->enum('whatsapp_session_status', ['disconnected', 'connected'])->default('disconnected');
            $table->string('whatsapp_phone_number')->nullable();
            $table->boolean('sms_enabled')->default(false);
            $table->integer('sms_balance')->default(0);
            $table->integer('sms_used_this_month')->default(0);

            // Domain & Website
            $table->string('subdomain', 100)->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->string('cloudflare_hostname_id')->nullable();
            $table->enum('domain_status', ['none', 'pending_cname', 'active'])->default('none');
            $table->string('template_key', 50)->default('general');
            $table->boolean('is_custom_standalone_website')->default(false);
            $table->string('public_booking_api_key', 64)->unique();
            $table->json('website_settings')->nullable();

            // Doctor's Dynamic Vitals & Print Setup
            $table->json('vitals_config')->nullable();
            $table->json('print_settings')->nullable();
            $table->json('notification_settings')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
