<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intervention extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'target_type',
        'target_value',
        'type',
        'description',
        'budget_allocated',
        'status',
        'expected_reduction_rate',
    ];

    protected $casts = [
        'budget_allocated' => 'decimal:2',
        'expected_reduction_rate' => 'integer',
    ];
}
