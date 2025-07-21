<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePublisherRequest extends FormRequest
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
            'name' => 'required|string',
            'logo' => 'nullable|image|max:5120',
            'address' => 'nullable|string',
            'website' => 'nullable|string',
            'email' => 'required|string|email|unique:publishers',
            'phone' => 'nullable|string',
            'established_year' => 'required|integer|max:' . date('Y'),
        ];
    }
}
