<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'gender',
        'caste',
        'date_of_birth',
        'standard',
        'status',
        'dropout_reason',
        'dropout_date',
        'area_village_city',
        'parent_income',
        'academic_year',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'dropout_date' => 'date',
        'standard' => 'integer',
        'parent_income' => 'decimal:2',
    ];

    /**
     * Get the school where the student is enrolled.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the attendance logs for the student.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get the subject performance marks for the student.
     */
    public function performances()
    {
        return $this->hasMany(StudentPerformance::class);
    }

    /**
     * Get the uploaded secure documents for the student.
     */
    public function documents()
    {
        return $this->hasMany(StudentDocument::class);
    }

    /**
     * Get the individual interventions active for this student.
     */
    public function studentInterventions()
    {
        return $this->hasMany(StudentIntervention::class);
    }

    /**
     * Get the feedback and complaints filed regarding this student.
     */
    public function complaints()
    {
        return $this->hasMany(FeedbackComplaint::class);
    }

    /**
     * Calculate individual student monthly average attendance percentage.
     */
    public function getAttendanceRateAttribute(): float
    {
        $total = $this->attendances()->count();
        if ($total === 0) {
            return 85.0; // Default baseline in case of no records
        }
        $present = $this->attendances()->whereIn('status', ['Present', 'Late'])->count();
        return round(($present / $total) * 100, 1);
    }

    /**
     * Calculate individual student overall academic grade percentage.
     */
    public function getAcademicAverageAttribute(): float
    {
        $marks = $this->performances();
        if ($marks->count() === 0) {
            return 72.5; // Baseline default in case of no records
        }
        return round($marks->avg('marks_obtained') ?: 0.0, 1);
    }

    /**
     * Calculate the dynamic rule-based AI Risk score and category.
     */
    public function getAiRiskStatusAttribute(): array
    {
        return (new \App\Services\DropoutPredictionService())->analyzeStudent($this);
    }

    /**
     * Scope a query to only include dropped out students.
     */
    public function scopeDroppedOut(Builder $query): Builder
    {
        return $query->where('status', 'Dropped Out');
    }

    /**
     * Scope a query to only include active/enrolled students.
     */
    public function scopeEnrolled(Builder $query): Builder
    {
        return $query->where('status', 'Enrolled');
    }

    /**
     * Get the student's age.
     */
    public function getAgeAttribute(): int
    {
        return Carbon::parse($this->date_of_birth)->age;
    }
}
