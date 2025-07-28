<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendResetLinkRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::exists('users', 'email')->where(function ($query) {
                    return $query->where('role_id', Role::LIBRARIAN);
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'The provided email address does not exist in our records.',
        ];
    }
}
