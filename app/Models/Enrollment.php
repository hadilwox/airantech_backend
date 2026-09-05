<?php

namespace App\Models;

use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EnrollmentStatus;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrolled_at', 'notes'])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enrolled_at' => 'date',
            'tuition_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_status' => EnrollmentPaymentStatus::class,
            'status' => EnrollmentStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $enrollment): void {
            $enrollment->payment_status = $enrollment->derivePaymentStatus();
        });
    }

    /**
     * payment_status is always derived from the amounts, never set directly
     * by a request — this is what stands in for a real ledger until the
     * Phase 3 payment/transaction system replaces it.
     */
    public function derivePaymentStatus(): EnrollmentPaymentStatus
    {
        if ((float) $this->paid_amount <= 0) {
            return EnrollmentPaymentStatus::Unpaid;
        }

        if ((float) $this->paid_amount >= (float) $this->tuition_amount) {
            return EnrollmentPaymentStatus::FullyPaid;
        }

        return EnrollmentPaymentStatus::PartiallyPaid;
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
