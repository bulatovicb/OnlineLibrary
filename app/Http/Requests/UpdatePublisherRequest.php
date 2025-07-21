<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublisherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string',
            'address' => 'nullable|string',
            'website' => 'nullable|string',
            'email' => [
                'sometimes',
                'string',
                'email',
                Rule::unique('publishers', 'email')->ignore($this->publisher->id)
            ],
            'phone' => 'nullable|string',
            'established_year' => 'nullable|integer|max:' . date('Y'),

        ];
    }
}
