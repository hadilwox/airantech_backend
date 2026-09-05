<?php

namespace App\Http\Requests;

use App\Enums\CourseStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('courses.update');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // category_id is intentionally not updatable — the course's
            // business `code` embeds the category prefix at creation time,
            // so changing category afterwards would desync code vs category.
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'teaching_hours' => ['nullable', 'integer', 'min:0'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'tuition_fee' => ['sometimes', 'required', 'numeric', 'min:0'],
            'instructor_cost' => ['nullable', 'numeric', 'min:0'],
            'has_university_certificate' => ['sometimes', 'boolean'],
            'exam_introduced_count' => ['sometimes', 'integer', 'min:0'],
            'exam_passed_count' => ['sometimes', 'integer', 'min:0'],
            'exam_retake_count' => ['sometimes', 'integer', 'min:0'],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => ['string', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'status' => ['sometimes', new Enum(CourseStatus::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
