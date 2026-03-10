<?php

namespace App\Http\Requests\Project;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProjectRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date'  => ['required', 'date', 'date_format:Y-m-d'],
            'end_date'    => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'          => 'Project title is required.',
            'start_date.required'     => 'Start date is required.',
            'start_date.date'         => 'Start date must be a valid date.',
            'end_date.required'       => 'End date is required.',
            'end_date.date'           => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            $this->validationErrorResponse($validator->errors())
        );
    }
}
