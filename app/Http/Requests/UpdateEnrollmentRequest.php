<?php

namespace App\Http\Requests;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('enrollments.update');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Enrollment $enrollment */
        $enrollment = $this->route('enrollment');

        return [
            'status' => ['sometimes', new Enum(EnrollmentStatus::class)],
            'paid_amount' => ['sometimes', 'numeric', 'min:0', 'max:'.$enrollment->tuition_amount],
            'notes' => ['nullable', 'string'],
            // tuition_amount/payment_status stay server-derived — never
            // accepted directly from the client, even on update.
        ];
    }
}
