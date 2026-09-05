<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_code' => $this->student_code,
            'national_id' => $this->national_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'father_name' => $this->father_name,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'mobile_phone' => $this->mobile_phone,
            'landline_phone' => $this->landline_phone,
            'emergency_phone' => $this->emergency_phone,
            'education_level' => $this->education_level,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'email' => $this->email,
            'is_active' => $this->whenLoaded('user', fn () => $this->user->is_active),
            'enrollments_count' => $this->whenCounted('enrollments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
