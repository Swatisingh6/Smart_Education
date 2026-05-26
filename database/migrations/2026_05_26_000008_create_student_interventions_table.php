<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_interventions', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('student_id')->constrained()->onDelete('cascade');
            $blueprint->string('intervention_type'); // Scholarship, Counseling, Transport, Parent Meeting
            $blueprint->text('details')->nullable();
            $blueprint->string('status')->default('Recommended'); // Recommended, Initiated, In Progress, Completed
            $blueprint->decimal('cost', 10, 2)->default(0.00);
            $blueprint->date('date_implemented')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_interventions');
    }
};
