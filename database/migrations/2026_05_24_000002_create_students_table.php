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
        Schema::create('students', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('school_id')->constrained()->onDelete('cascade');
            $blueprint->string('name');
            $blueprint->string('gender'); // Male, Female, Transgender
            $blueprint->string('caste'); // General, OBC, SC, ST
            $blueprint->date('date_of_birth');
            $blueprint->integer('standard'); // 1 to 12
            $blueprint->string('status')->default('Enrolled'); // Enrolled, Dropped Out
            $blueprint->string('dropout_reason')->nullable(); // Poverty, Distance to School, Family Migration, Child Labor, Household Work, Marriage, Sanitation/Social, Lack of Interest
            $blueprint->date('dropout_date')->nullable();
            $blueprint->string('area_village_city')->default('Jaipur');
            $blueprint->decimal('parent_income', 10, 2)->default(120000.00);
            $blueprint->string('academic_year')->default('2025-2026');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
