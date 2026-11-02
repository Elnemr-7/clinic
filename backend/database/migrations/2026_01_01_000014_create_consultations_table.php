<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('appointment_id')->unique()->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();

            $table->text('complaints_symptoms')->nullable();
            $table->text('diagnosis');
            $table->text('private_doctor_notes')->nullable(); // strictly hidden from printed rx & patient
            $table->json('prescribed_drugs')->nullable(); // [{drug_name, dosage, duration, instructions}]
            $table->json('requested_labs')->nullable();    // [{test_name, type, notes}]
            $table->json('selected_services')->nullable(); // [{service_id, name, price}]
            $table->date('followup_date')->nullable();
            $table->string('pdf_short_token', 16)->unique();

            $table->timestamps();

            $table->index(['tenant_id', 'patient_id']);
            $table->index('pdf_short_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
