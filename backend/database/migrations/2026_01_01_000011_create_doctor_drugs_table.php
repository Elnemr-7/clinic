<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_drugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('drug_name');
            $table->string('form_type')->nullable(); // أقراص، شراب، حقن...
            $table->string('default_dosage')->nullable();
            $table->string('default_duration')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'drug_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_drugs');
    }
};
