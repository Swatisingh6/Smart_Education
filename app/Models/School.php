<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'area_type',
        'district',
        'pincode',
    ];

    /**
     * Get the students enrolled in this school.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
