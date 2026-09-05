<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('categories.update');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('course_categories', 'name')->ignore($this->route('course_category'))],
            'code_prefix' => ['sometimes', 'required', 'string', 'max:10', Rule::unique('course_categories', 'code_prefix')->ignore($this->route('course_category'))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
