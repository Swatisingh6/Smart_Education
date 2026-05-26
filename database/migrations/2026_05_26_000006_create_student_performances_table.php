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
        Schema::create('student_performances', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('student_id')->constrained()->onDelete('cascade');
            $blueprint->string('subject'); // Mathematics, Science, Social Studies, English, Regional Language
            $blueprint->integer('marks_obtained');
            $blueprint->integer('max_marks')->default(100);
            $blueprint->string('term'); // Term 1, Term 2, Final Exam
            $blueprint->string('academic_year');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_performances');
    }
};
