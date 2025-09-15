<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required',
            'email' => [
                'required',
                'email',
                Rule::exists('users', 'email'),
            ],
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}
