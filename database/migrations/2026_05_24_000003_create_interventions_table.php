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
        Schema::create('interventions', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->string('target_type'); // School, Area, Gender, Caste, Standard, All
            $blueprint->string('target_value')->nullable(); // Target identifier (e.g., "Female", "Rural", "SC", "Standard 9", or a school name)
            $blueprint->string('type'); // Meal, Transport, Scholarship, Counseling, Infrastructure
            $blueprint->text('description')->nullable();
            $blueprint->decimal('budget_allocated', 15, 2)->default(0.00);
            $blueprint->string('status')->default('Planned'); // Planned, Active, Completed
            $blueprint->integer('expected_reduction_rate')->default(10); // Percentage reduction (e.g. 15 for 15% reduction)
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
