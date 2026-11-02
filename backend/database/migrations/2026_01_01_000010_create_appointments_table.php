<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('clinic_shifts')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();

            $table->date('appointment_date')->index();
            $table->integer('queue_number');
            $table->string('reference_code', 10)->unique();
            $table->enum('booking_source', ['online', 'clinic_desk', 'phone'])->default('clinic_desk');
            $table->enum('visit_type', ['new_visit', 'followup', 'emergency', 'procedure'])->default('new_visit');
            $table->enum('status', [
                'pending_payment',
                'booked',
                'arrived_waiting',
                'in_consultation',
                'postponed_waiting_arrival',
                'completed',
                'cancelled'
            ])->default('booked')->index();

            // Dynamic Time Window Fields
            $table->time('estimated_window_start')->nullable();
            $table->time('estimated_window_end')->nullable();
            $table->dateTime('actual_entered_at')->nullable();
            $table->dateTime('actual_completed_at')->nullable();

            // Vitals recorded by receptionist
            $table->json('vitals_data')->nullable();

            // Financials
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->decimal('extra_services_total', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->boolean('extra_services_paid')->default(false);

            // Messaging
            $table->boolean('whatsapp_opted_in')->default(false);
            $table->boolean('near_turn_notified')->default(false);

            $table->timestamps();

            $table->index(['clinic_id', 'appointment_date', 'shift_id']);
            $table->unique(['clinic_id', 'shift_id', 'appointment_date', 'queue_number'], 'unique_queue_per_shift');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
