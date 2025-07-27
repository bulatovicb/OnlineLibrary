<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
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
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'jmbg' => 'required|regex:/^\d{13}$/|unique:users,jmbg',
            'role_id' => 'required|exists:roles,id',
            'profile_picture' => 'nullable|image|max:5120',
            'password' => 'required|min:8',
        ];
    }
}
