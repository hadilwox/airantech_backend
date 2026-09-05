<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('enrollments.create');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'enrolled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            // tuition_amount/payment_status/status are deliberately not
            // accepted here — the server always derives them (see
            // EnrollmentController@store).
        ];
    }
}
