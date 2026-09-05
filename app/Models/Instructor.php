<?php

namespace App\Models;

use App\Enums\InstructorStatus;
use Database\Factories\InstructorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'first_name',
    'last_name',
    'father_name',
    'national_id',
    'residence',
    'marital_status',
    'date_of_birth',
    'mobile_phone',
    'landline_phone',
    'emergency_phone',
    'education_degree',
    'expected_salary',
    'skills',
    'work_experience',
    'status',
])]
class Instructor extends Model
{
    /** @use HasFactory<InstructorFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'expected_salary' => 'decimal:2',
            'status' => InstructorStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
