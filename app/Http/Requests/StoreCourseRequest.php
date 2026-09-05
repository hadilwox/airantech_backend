<?php

namespace App\Http\Requests;

use App\Enums\CourseStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('courses.create');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:course_categories,id'],
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'title' => ['required', 'string', 'max:255'],
            'teaching_hours' => ['nullable', 'integer', 'min:0'],
            'capacity' => ['required', 'integer', 'min:1'],
            'tuition_fee' => ['required', 'numeric', 'min:0'],
            'instructor_cost' => ['nullable', 'numeric', 'min:0'],
            'has_university_certificate' => ['sometimes', 'boolean'],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => ['string', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'status' => ['sometimes', new Enum(CourseStatus::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
