<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = auth()->id();

        return [
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'email' => [
                'sometimes',
                'string',
                'email',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'username' => [
                'sometimes',
                'string',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'jmbg' => [
                'sometimes',
                'regex:/^\d{13}$/',
            ],
        ];
    }
}
