<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'jalali_year' => $this->jalali_year,
            'jalali_month' => $this->jalali_month,
            'title' => $this->title,
            'category' => new CourseCategoryResource($this->whenLoaded('category')),
            'instructor' => new InstructorResource($this->whenLoaded('instructor')),
            'teaching_hours' => $this->teaching_hours,
            'capacity' => $this->capacity,
            'tuition_fee' => $this->tuition_fee,
            'instructor_cost' => $this->instructor_cost,
            'has_university_certificate' => $this->has_university_certificate,
            'exam_introduced_count' => $this->exam_introduced_count,
            'exam_passed_count' => $this->exam_passed_count,
            'exam_retake_count' => $this->exam_retake_count,
            'schedule_days' => $this->schedule_days,
            'status' => $this->status,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'description' => $this->description,
            'enrollments_count' => $this->whenCounted('enrollments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
