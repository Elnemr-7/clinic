<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 50);
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->smallInteger('birth_year')->nullable();
            $table->string('address')->nullable();
            $table->text('chronic_diseases')->nullable(); // RED ALERT banner
            $table->text('drug_allergies')->nullable();   // RED ALERT banner
            $table->text('medical_notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'phone']);
            $table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
