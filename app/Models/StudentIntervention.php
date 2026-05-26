<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentIntervention extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'intervention_type',
        'details',
        'status',
        'cost',
        'date_implemented',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'date_implemented' => 'date',
    ];

    /**
     * Get the student associated with this intervention action.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
