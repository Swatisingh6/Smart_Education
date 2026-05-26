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
        Schema::create('attendances', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('student_id')->constrained()->onDelete('cascade');
            $blueprint->date('date');
            $blueprint->string('status'); // Present, Absent, Late
            $blueprint->string('remarks')->nullable();
            $blueprint->timestamps();

            // Ensure unique attendance per student per day
            $blueprint->unique(['student_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
