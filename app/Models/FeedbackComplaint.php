<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackComplaint extends Model
{
    use HasFactory;

    protected $table = 'feedback_complaints';

    protected $fillable = [
        'student_id',
        'user_id',
        'name',
        'type',
        'description',
        'status',
        'response',
    ];

    /**
     * Get the student associated with this complaint (if any).
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the user associated with this complaint (if any).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
