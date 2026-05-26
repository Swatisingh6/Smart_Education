<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject',
        'marks_obtained',
        'max_marks',
        'term',
        'academic_year',
    ];

    /**
     * Get the student associated with this grade sheet.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
