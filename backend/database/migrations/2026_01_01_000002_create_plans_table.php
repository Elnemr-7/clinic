<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->integer('max_clinics')->default(1); // -1 = Unlimited
            $table->integer('free_whatsapp_quota_monthly')->default(0);
            $table->integer('free_sms_quota_monthly')->default(0);
            $table->boolean('allow_custom_domain')->default(false);
            $table->boolean('allow_custom_website')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
