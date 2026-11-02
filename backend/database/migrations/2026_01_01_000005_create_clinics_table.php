<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name_ar');
            $table->text('address_ar')->nullable();
            $table->string('city_ar')->nullable();
            $table->string('phone')->nullable();
            $table->text('google_maps_url')->nullable();
            $table->decimal('consultation_price', 10, 2)->default(0);
            $table->decimal('followup_price', 10, 2)->default(0);
            $table->integer('followup_days_limit')->default(10);
            $table->integer('avg_consultation_minutes')->default(15);
            $table->enum('booking_payment_mode', ['pay_at_clinic', 'require_prepayment'])->default('pay_at_clinic');
            $table->text('prepayment_instructions_ar')->nullable();
            $table->string('tv_screen_code', 10)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
