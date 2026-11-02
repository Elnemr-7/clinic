<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('template_name');
            $table->text('diagnosis_default')->nullable();
            $table->json('drugs_json'); // array of [{drug_name, dosage, duration, instructions}]
            $table->timestamps();

            $table->index(['tenant_id', 'template_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_templates');
    }
};
