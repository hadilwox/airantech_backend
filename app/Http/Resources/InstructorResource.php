<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'father_name' => $this->father_name,
            'national_id' => $this->national_id,
            'residence' => $this->residence,
            'marital_status' => $this->marital_status,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'mobile_phone' => $this->mobile_phone,
            'landline_phone' => $this->landline_phone,
            'emergency_phone' => $this->emergency_phone,
            'education_degree' => $this->education_degree,
            'expected_salary' => $this->expected_salary,
            'skills' => $this->skills,
            'work_experience' => $this->work_experience,
            'status' => $this->status,
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'is_active' => $this->whenLoaded('user', fn () => $this->user->is_active),
            'courses_count' => $this->whenCounted('courses'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
