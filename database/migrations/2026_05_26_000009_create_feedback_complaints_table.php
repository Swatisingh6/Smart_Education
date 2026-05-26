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
        Schema::create('feedback_complaints', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('student_id')->nullable()->constrained()->onDelete('cascade');
            $blueprint->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $blueprint->string('name');
            $blueprint->string('type'); // Complaint, Financial Issue, Transport Issue, School Issue
            $blueprint->text('description');
            $blueprint->string('status')->default('Pending'); // Pending, Investigating, Resolved
            $blueprint->text('response')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_complaints');
    }
};
