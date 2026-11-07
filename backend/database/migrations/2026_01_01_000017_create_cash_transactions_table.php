<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('type', ['income', 'expense'])->default('income');
            $table->enum('category', [
                'consultation',
                'followup',
                'medical_service',
                'clinic_supplies',
                'utilities',
                'other_expense',
                'refund'
            ])->default('consultation');
            $table->decimal('amount', 10, 2);
            $table->string('description_ar')->nullable();
            $table->date('transaction_date')->index();
            $table->timestamps();

            $table->index(['clinic_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
