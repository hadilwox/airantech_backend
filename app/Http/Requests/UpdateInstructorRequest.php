<?php

namespace App\Http\Requests;

use App\Enums\InstructorStatus;
use App\Models\Instructor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->has('status') && ! $this->user()->can('instructors.approve')) {
            return false;
        }

        return $this->user()->can('instructors.update');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Instructor $instructor */
        $instructor = $this->route('instructor');

        return [
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($instructor->user_id)],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'national_id' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('instructors', 'national_id')->ignore($instructor->id)],
            'residence' => ['nullable', 'string'],
            'marital_status' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'mobile_phone' => ['sometimes', 'required', 'string', 'max:20'],
            'landline_phone' => ['nullable', 'string', 'max:20'],
            'emergency_phone' => ['nullable', 'string', 'max:20'],
            'education_degree' => ['nullable', 'string', 'max:255'],
            'expected_salary' => ['nullable', 'numeric', 'min:0'],
            'skills' => ['nullable', 'string'],
            'work_experience' => ['nullable', 'string'],
            'status' => ['sometimes', new Enum(InstructorStatus::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
