<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_days_off', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->date('date');
            $table->string('reason_ar')->nullable();
            $table->timestamps();

            $table->unique(['clinic_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_days_off');
    }
};
