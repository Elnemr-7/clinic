<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_leads', function (Blueprint $table) {
            $table->id();
            $table->string('doctor_name');
            $table->string('phone')->index();
            $table->string('specialty');
            $table->foreignId('requested_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->enum('status', ['new', 'contacted', 'converted', 'rejected'])->default('new')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_leads');
    }
};
